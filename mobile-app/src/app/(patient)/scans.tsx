import React, { useState } from 'react';
import {
  FlatList,
  StyleSheet,
  Text,
  View,
  Image as RNImage,
  TouchableOpacity,
  Alert,
  ActivityIndicator,
  Platform,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { router } from 'expo-router';
import { useScans } from '../../hooks/useApi';
import {
  orvellaColors,
  orvellaFontSize,
  orvellaSpacing,
  orvellaRadius,
  orvellaShadow,
} from '../../constants/orvella';
import { Card } from '../../components/ui/Card';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { LoadingState, EmptyState } from '../../components/ui/StateViews';

import * as Print from 'expo-print';
import * as Sharing from 'expo-sharing';
import * as FileSystem from 'expo-file-system/legacy';


import { BASE_URL } from '../../api/client';

const getFullImageUrl = (url: string) => {
  if (!url) return '';
  
  let resolvedUrl = url;
  // If the url points to localhost or 127.0.0.1 on mobile, replace it with the LAN IP from BASE_URL
  if (url.startsWith('http://') || url.startsWith('https://')) {
    if (Platform.OS !== 'web' && (url.includes('localhost') || url.includes('127.0.0.1'))) {
      const baseIpMatch = BASE_URL.match(/http:\/\/([^:/]+)/);
      if (baseIpMatch && baseIpMatch[1]) {
        const lanIp = baseIpMatch[1];
        resolvedUrl = url
          .replace('localhost', lanIp)
          .replace('127.0.0.1', lanIp);
      }
    }
    return resolvedUrl;
  }
  return `${BASE_URL}/${url.startsWith('/') ? url.slice(1) : url}`;
};

const urlToBase64 = async (url: string): Promise<string | null> => {
  try {
    const filename = url.split('/').pop() || 'temp_scan.jpg';
    const localUri = `${FileSystem.cacheDirectory}img_${filename}`;

    console.log('[urlToBase64] Native downloading image from:', url);
    
    let downloadResult;
    try {
      downloadResult = await FileSystem.downloadAsync(url, localUri);
    } catch (downErr: any) {
      Alert.alert('Download Error', `URL: ${url}\nError: ${downErr?.message || downErr}`);
      return null;
    }

    if (downloadResult.status !== 200) {
      console.warn('[urlToBase64] HTTP download failed with status:', downloadResult.status);
      Alert.alert('Download HTTP Error', `URL: ${url}\nStatus: ${downloadResult.status}`);
      return null;
    }

    console.log('[urlToBase64] Reading local image as base64...');
    const b64 = await FileSystem.readAsStringAsync(localUri, {
      encoding: FileSystem.EncodingType.Base64,
    });

    const ext = filename.split('.').pop()?.toLowerCase();
    const mimeType = ext === 'png' ? 'image/png' : 'image/jpeg';

    // Clean up temporary image file
    try {
      await FileSystem.deleteAsync(localUri, { idempotent: true });
    } catch (delErr) {
      console.warn('[urlToBase64] Failed to delete temp image file:', delErr);
    }

    return `data:${mimeType};base64,${b64}`;
  } catch (err: any) {
    console.warn('[urlToBase64] Failed:', err?.message || err);
    Alert.alert('Conversion Exception', err?.message || String(err));
    return null;
  }
};

export default function ScansScreen() {
  const { data: scansData, isLoading, refetch } = useScans();
  const insets = useSafeAreaInsets();
  const scans = (scansData?.data || []).filter((item: any) => item.status === 'approved');

  const [failedImages, setFailedImages] = useState<Record<number, boolean>>({});
  const [generatingIds, setGeneratingIds] = useState<Record<number, boolean>>({});
  const [expandedCardId, setExpandedCardId] = useState<number | null>(null);

  const handleImageError = (id: number) => {
    setFailedImages((prev) => ({ ...prev, [id]: true }));
  };

  const handleDownloadPDF = async (item: any) => {
    if (!item?.id) {
      console.error('[PDF] Scan item is invalid:', item);
      Alert.alert('Error', 'Scan data not found. Please refresh and try again.');
      return;
    }

    console.log('[PDF] Starting export for scan ID:', item.id);
    setGeneratingIds((prev) => ({ ...prev, [item.id]: true }));

    try {
      // ── 1. Parse prescription from diagnosis notes ──────────────────────────
      let clinicalNotes = item.diagnosis?.notes || '';
      let prescription: any = null;
      if (clinicalNotes.trim().startsWith('{')) {
        try {
          prescription = JSON.parse(clinicalNotes);
          clinicalNotes = prescription.medical_notes || '';
        } catch {
          prescription = null;
        }
      }

      // ── 2. Date strings ────────────────────────────────────────────────────
      const scanDate = new Date(item.created_at).toLocaleDateString('en-US', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
      });
      const reviewDate = item.diagnosis?.updated_at
        ? new Date(item.diagnosis.updated_at).toLocaleDateString('en-US', {
            weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
          })
        : scanDate;

      const doctorName = item.doctor?.full_name
        ? `Dr. ${item.doctor.full_name}`
        : 'Specialist Pulmonologist';

      // ── 3. Embed scan image ───
      let imageHtml: string;
      if (item.image_url) {
        const fullUrl = getFullImageUrl(item.image_url);
        imageHtml = `<img src="${fullUrl}" style="width:100%;max-height:320px;object-fit:contain;border-radius:12px;border:1px solid #E2E8F0;" />`;
      } else {
        console.warn('[PDF] No image_url on scan ID:', item.id);
        imageHtml = `<div style="background:#F1F5F9;color:#64748B;padding:40px;text-align:center;border-radius:12px;border:1px solid #E2E8F0;font-size:13px;font-style:italic;">No Image Provided for this scan.</div>`;
      }

      // ── 4. Build prescription table HTML ──────────────────────────────────
      let prescHtml = '';
      if (prescription) {
        const rows: string[] = [];
        if (prescription.next_checkup)
          rows.push(`<tr><td style="font-weight:600;color:#3B4B8C;padding:8px 0;">Next Check-up</td><td style="padding:8px 0;">${prescription.next_checkup}</td></tr>`);
        if (prescription.food_allowed)
          rows.push(`<tr><td style="font-weight:600;color:#15803D;padding:8px 0;">Recommended Foods</td><td style="padding:8px 0;">${prescription.food_allowed}</td></tr>`);
        if (prescription.food_avoided)
          rows.push(`<tr><td style="font-weight:600;color:#B91C1C;padding:8px 0;">Foods to Avoid</td><td style="padding:8px 0;">${prescription.food_avoided}</td></tr>`);
        if (prescription.recommended_activities)
          rows.push(`<tr><td style="font-weight:600;color:#1D4ED8;padding:8px 0;">Recommended Activities</td><td style="padding:8px 0;">${prescription.recommended_activities}</td></tr>`);
        if (prescription.avoided_activities)
          rows.push(`<tr><td style="font-weight:600;color:#B45309;padding:8px 0;">Activities to Avoid</td><td style="padding:8px 0;">${prescription.avoided_activities}</td></tr>`);
        if (prescription.lifestyle_recommendations)
          rows.push(`<tr><td style="font-weight:600;color:#6D28D9;padding:8px 0;">Lifestyle Recommendations</td><td style="padding:8px 0;">${prescription.lifestyle_recommendations}</td></tr>`);
        if (prescription.additional_notes)
          rows.push(`<tr><td style="font-weight:600;color:#475569;padding:8px 0;">Additional Notes</td><td style="padding:8px 0;">${prescription.additional_notes}</td></tr>`);
        if (rows.length > 0) {
          prescHtml = `<table style="width:100%;border-collapse:collapse;font-size:13px;margin-top:8px;">${rows.map(r => r.replace('<tr>', '<tr style="border-bottom:1px solid #E2E8F0;">')).join('')}</table>`;
        }
      }

      const patientName = item.patient?.full_name || item.patient?.user?.full_name || 'Patient';
      const patientGender = item.patient?.gender || 'N/A';
      const patientAge = item.patient?.birth_date
        ? `${new Date().getFullYear() - new Date(item.patient.birth_date).getFullYear()} Years`
        : 'N/A';

      // ── 5. Build HTML document ─────────────────────────────────────────────
      const html = `<!DOCTYPE html><html><head><meta charset="utf-8" /><title>Medical Scan Report</title><style>
        body{font-family:'Helvetica Neue',Arial,sans-serif;margin:0;padding:24px;color:#1E293B;background:#ffffff;line-height:1.5;}
        h1{margin:0 0 16px;font-size:24px;color:#1E3A8A;font-weight:700;text-align:center;}
        h3{font-size:14px;font-weight:700;color:#1E3A8A;text-transform:uppercase;letter-spacing:0.5px;margin:24px 0 6px;}
        hr{border:0;border-top:1.5px solid #CBD5E1;margin:4px 0 14px 0;}
        .info-table{width:100%;border-collapse:collapse;margin-bottom:8px;}
        .info-table td{padding:6px 0;font-size:13px;vertical-align:top;}
        .info-table td:first-child{color:#64748B;width:140px;font-weight:600;}
        .info-table td:last-child{color:#0F172A;}
        .notes-box{background:#F8FAFC;border-left:4px solid #1E3A8A;padding:12px 16px;border-radius:6px;font-style:italic;font-size:13px;color:#334155;margin-top:6px;}
        .footer{margin-top:36px;padding-top:16px;border-top:1px solid #E2E8F0;font-size:11px;color:#94A3B8;text-align:center;}
      </style></head><body>
        <h1>Medical Scan Report</h1>
        <h3>Patient Information</h3><hr />
        <table class="info-table">
          <tr><td>Patient Name</td><td>${patientName}</td></tr>
          <tr><td>Age</td><td>${patientAge}</td></tr>
          <tr><td>Gender</td><td>${patientGender}</td></tr>
        </table>
        <h3>Scan Information</h3><hr />
        <table class="info-table">
          <tr><td>Scan ID</td><td>#${String(item.id).padStart(4, '0')}</td></tr>
          <tr><td>Date</td><td>${scanDate}</td></tr>
          <tr><td>Type</td><td>Cervical Cancer Screening</td></tr>
          <tr><td>Status</td><td>${item.status?.toUpperCase() || 'APPROVED'}</td></tr>
        </table>
        <h3>Scan Image</h3><hr />
        <div style="text-align:center;margin-bottom:12px;">${imageHtml}</div>
        <h3>Doctor Review</h3><hr />
        <table class="info-table">
          <tr><td>Attending Doctor</td><td>${doctorName}</td></tr>
          <tr><td>Review Date</td><td>${reviewDate}</td></tr>
          <tr><td>Diagnosis</td><td style="font-weight:600;color:#1E3A8A;">${item.diagnosis?.diagnosis_result || 'Verified Cervical Scan Analysis'}</td></tr>
        </table>
        <div class="notes-box"><strong>Clinical Notes:</strong><br/>"${clinicalNotes || 'No notes provided by specialist.'}"</div>
        ${prescHtml ? `<div style="margin-top:16px;"><strong>Prescriptions &amp; Recommendations:</strong>${prescHtml}</div>` : ''}
        <div class="footer">Generated by Orvella Health Platform &middot; Digitally Signed and Verified</div>
      </body></html>`;

      // ── 6. Generate PDF file with Base64 encoding ─────────────────────────
      console.log('[PDF] Calling Print.printToFileAsync with base64: true...');
      let pdf: { uri: string; base64?: string };
      try {
        pdf = await Print.printToFileAsync({ html, base64: true });
      } catch (printErr: any) {
        throw new Error(`printToFileAsync failed: ${printErr?.message || printErr}`);
      }

      console.log('[PDF] PDF generated internally. URI:', pdf.uri);

      if (!pdf.base64) {
        throw new Error('printToFileAsync did not return base64 data');
      }

      // ── 7. Write PDF base64 directly into the sandboxed cache directory ────
      const pdfFilename = pdf.uri.split('/').pop() || `scan_${item.id}.pdf`;
      const shareableUri = `${FileSystem.cacheDirectory}${pdfFilename}`;

      console.log('[PDF] Writing base64 to sandboxed experience cache path:', shareableUri);
      await FileSystem.writeAsStringAsync(shareableUri, pdf.base64, {
        encoding: FileSystem.EncodingType.Base64,
      });

      // ── 8. Share the copied file ───────────────────────────────────────────
      const canShare = await Sharing.isAvailableAsync();
      if (!canShare) {
        try {
          await FileSystem.deleteAsync(shareableUri, { idempotent: true });
        } catch (delErr) {
          console.warn('[PDF] Clean up failed:', delErr);
        }
        Alert.alert('PDF Ready', `Report generated but sharing is not available on this device.\nURI: ${shareableUri}`);
        return;
      }

      console.log('[PDF] Sharing from URI:', shareableUri);
      await Sharing.shareAsync(shareableUri, {
        mimeType: 'application/pdf',
        dialogTitle: `Cervical Scan Report #${String(item.id).padStart(4, '0')}`,
        UTI: 'com.adobe.pdf',
      });
      console.log('[PDF] Share dialog completed successfully');
      Alert.alert('Download Successful', 'The report PDF file has been processed and is ready to be saved or shared.');

      // ── 9. Delete the temporary copied file after sharing ──────────────────
      try {
        console.log('[PDF] Deleting temporary shareable file:', shareableUri);
        await FileSystem.deleteAsync(shareableUri, { idempotent: true });
      } catch (cleanupErr) {
        console.warn('[PDF] Failed to delete temporary file:', cleanupErr);
      }

    } catch (err: any) {
      console.error('[PDF Export Error]:', err?.message || err);
      Alert.alert('Error', 'Could not generate or share the PDF report. Please try again.');
    } finally {
      setGeneratingIds((prev) => ({ ...prev, [item.id]: false }));
    }
  };

  if (isLoading) {
    return <LoadingState message="Loading Cervical Scan results..." />;
  }

  const renderInfoRow = (icon: string, label: string, value: string, color?: string) => (
    <View style={styles.infoRow}>
      <View style={[styles.infoIconBox, color ? { backgroundColor: color + '18' } : {}]}>
        <Ionicons name={icon as any} size={14} color={color || orvellaColors.textSecondary} />
      </View>
      <View style={styles.infoContent}>
        <Text style={styles.infoLabel}>{label}</Text>
        <Text style={styles.infoValue}>{value}</Text>
      </View>
    </View>
  );

  const renderPrescriptionSection = (prescription: any) => (
    <View style={styles.prescriptionContainer}>
      {prescription.next_checkup ? (
        <View style={styles.nextCheckupCard}>
          <View style={[styles.nextCheckupLeft, { flex: 1 }]}>
            <Ionicons name="calendar" size={20} color={orvellaColors.primary} />
            <View style={{ marginLeft: 10, flex: 1 }}>
              <Text style={styles.nextCheckupLabel}>Next Follow-up Appointment</Text>
              <Text style={styles.nextCheckupValue}>{prescription.next_checkup}</Text>
            </View>
          </View>
        </View>
      ) : null}

      <View style={styles.prescGrid}>
        {prescription.food_allowed ? (
          <View style={[styles.prescItem, { backgroundColor: '#F0FDF4', borderColor: '#BBF7D0' }]}>
            <View style={styles.prescItemHeader}>
              <Ionicons name="checkmark-circle" size={16} color="#16A34A" />
              <Text style={[styles.prescItemTitle, { color: '#15803D' }]}>Recommended Foods</Text>
            </View>
            <Text style={styles.prescItemText}>{prescription.food_allowed}</Text>
          </View>
        ) : null}
        {prescription.food_avoided ? (
          <View style={[styles.prescItem, { backgroundColor: '#FFF1F2', borderColor: '#FECDD3' }]}>
            <View style={styles.prescItemHeader}>
              <Ionicons name="close-circle" size={16} color="#DC2626" />
              <Text style={[styles.prescItemTitle, { color: '#B91C1C' }]}>Foods to Avoid</Text>
            </View>
            <Text style={styles.prescItemText}>{prescription.food_avoided}</Text>
          </View>
        ) : null}
        {prescription.recommended_activities ? (
          <View style={[styles.prescItem, { backgroundColor: '#EFF6FF', borderColor: '#BFDBFE' }]}>
            <View style={styles.prescItemHeader}>
              <Ionicons name="fitness" size={16} color="#2563EB" />
              <Text style={[styles.prescItemTitle, { color: '#1D4ED8' }]}>Recommended Activities</Text>
            </View>
            <Text style={styles.prescItemText}>{prescription.recommended_activities}</Text>
          </View>
        ) : null}
        {prescription.avoided_activities ? (
          <View style={[styles.prescItem, { backgroundColor: '#FFFBEB', borderColor: '#FDE68A' }]}>
            <View style={styles.prescItemHeader}>
              <Ionicons name="warning" size={16} color="#D97706" />
              <Text style={[styles.prescItemTitle, { color: '#B45309' }]}>Activities to Avoid</Text>
            </View>
            <Text style={styles.prescItemText}>{prescription.avoided_activities}</Text>
          </View>
        ) : null}
      </View>

      {prescription.lifestyle_recommendations ? (
        <View style={[styles.prescFullItem, { backgroundColor: '#FAF5FF', borderColor: '#E9D5FF' }]}>
          <View style={styles.prescItemHeader}>
            <Ionicons name="heart" size={16} color="#7C3AED" />
            <Text style={[styles.prescItemTitle, { color: '#6D28D9' }]}>Lifestyle Recommendations</Text>
          </View>
          <Text style={styles.prescItemText}>{prescription.lifestyle_recommendations}</Text>
        </View>
      ) : null}

      {prescription.additional_notes ? (
        <View style={[styles.prescFullItem, { backgroundColor: '#F8FAFC', borderColor: '#E2E8F0' }]}>
          <View style={styles.prescItemHeader}>
            <Ionicons name="document-text" size={16} color={orvellaColors.textSecondary} />
            <Text style={[styles.prescItemTitle, { color: orvellaColors.textPrimary }]}>Additional Notes</Text>
          </View>
          <Text style={styles.prescItemText}>{prescription.additional_notes}</Text>
        </View>
      ) : null}
    </View>
  );

  const renderClinicalSection = (item: any) => {
    if (!item.diagnosis) return null;

    let clinicalNotes = item.diagnosis.notes || '';
    let prescription: any = null;
    if (clinicalNotes.trim().startsWith('{')) {
      try {
        prescription = JSON.parse(clinicalNotes);
        clinicalNotes = prescription.medical_notes || '';
      } catch {
        prescription = null;
      }
    }
    const doctorName = item.doctor?.full_name ? `Dr. ${item.doctor.full_name}` : 'Specialist Pulmonologist';

    return (
      <View style={styles.clinicalCard}>
        {/* Clinical Card Header */}
        <View style={styles.clinicalCardHeader}>
          <View style={styles.clinicalTitleRow}>
            <View style={styles.clinicalIconBox}>
              <Ionicons name="shield-checkmark" size={16} color={orvellaColors.success} />
            </View>
            <View style={{ flex: 1 }}>
              <Text style={styles.clinicalTitle}>Specialist Review</Text>
              <Text style={styles.clinicalSubtitle}>Pulmonology & Clinical Verification</Text>
            </View>
          </View>
          <View style={styles.verifiedBadge}>
            <Ionicons name="checkmark" size={10} color="#ffffff" />
            <Text style={styles.verifiedBadgeText}>VERIFIED</Text>
          </View>
        </View>

        {/* Doctor Notes */}
        {clinicalNotes ? (
          <View style={styles.notesBox}>
            <Text style={styles.notesQuote}>"{clinicalNotes}"</Text>
          </View>
        ) : null}

        {/* Prescription details */}
        {prescription ? renderPrescriptionSection(prescription) : null}

        {/* Footer */}
        <View style={styles.clinicalFooter}>
          <Ionicons name="person-circle-outline" size={16} color={orvellaColors.textSecondary} />
          <Text style={styles.clinicalDoctorText}>
            Reviewed & Certified by: {doctorName}
          </Text>
        </View>
      </View>
    );
  };

  return (
    <View style={styles.container}>
      {/* Premium Centered Header with Back Button */}
      <View style={[styles.header, { paddingTop: Math.max(insets.top, 16) }]}>
        <TouchableOpacity 
          style={styles.backButton} 
          onPress={() => router.push('/(patient)/dashboard')}
          activeOpacity={0.7}
        >
          <Ionicons name="arrow-back" size={20} color="#1E293B" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Scan Results</Text>
        <View style={{ width: 40 }} />
      </View>

      <FlatList
        data={scans}
        keyExtractor={(item, index) => `${item.id}-${index}`}
        refreshing={isLoading}
        onRefresh={refetch}
        contentContainerStyle={styles.listContainer}
        ListEmptyComponent={
          <EmptyState
            title="No History Found"
            subtitle="You do not have any Cervical Scan history available in Orvella."
          />
        }
        renderItem={({ item }) => {
          const isExpanded = expandedCardId === item.id;
          const imageFailed = failedImages[item.id] || !item.image_url;
          const scanDate = new Date(item.created_at).toLocaleDateString('en-US', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
          });
          const fullImageUrl = getFullImageUrl(item.image_url);
          const isGenerating = !!generatingIds[item.id];
          const doctorName = item.doctor?.full_name ? `Dr. ${item.doctor.full_name}` : 'Specialist Doctor';

          return (
            <View style={styles.card}>
              {/* Card Header (Tap to Expand/Collapse) */}
              <TouchableOpacity
                style={styles.cardHeaderInteractive}
                onPress={() => setExpandedCardId(isExpanded ? null : item.id)}
                activeOpacity={0.8}
              >
                <View style={styles.scanTypeRow}>
                  <View style={styles.scanIconBox}>
                    <Ionicons name="scan" size={20} color={orvellaColors.primary} />
                  </View>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.scanTypeTitle}>Cervical Cancer Screening</Text>
                    <Text style={styles.scanMeta}>ID: #{String(item.id).padStart(4, '0')} · {scanDate}</Text>
                  </View>
                </View>
                <View style={styles.headerRightCol}>
                  <StatusBadge status={item.status} />
                  <View style={styles.chevronBox}>
                    <Ionicons 
                      name={isExpanded ? "chevron-up" : "chevron-down"} 
                      size={16} 
                      color={orvellaColors.primary} 
                    />
                  </View>
                </View>
              </TouchableOpacity>

              {/* Condensed preview info if collapsed */}
              {!isExpanded && (
                <View style={styles.collapsedSummary}>
                  <View style={styles.collapsedAttendingRow}>
                    <Ionicons name="person-circle-outline" size={16} color={orvellaColors.textSecondary} />
                    <Text style={styles.collapsedSummaryText}>
                      Attending: <Text style={{ fontWeight: '700', color: '#1A2340' }}>{doctorName}</Text>
                    </Text>
                  </View>
                  <TouchableOpacity
                    style={styles.viewDetailsLink}
                    onPress={() => setExpandedCardId(item.id)}
                    activeOpacity={0.7}
                  >
                    <Text style={styles.viewDetailsLinkText}>View Report</Text>
                    <Ionicons name="arrow-forward" size={11} color={orvellaColors.primary} style={{ marginLeft: 3 }} />
                  </TouchableOpacity>
                </View>
              )}

              {/* Full Details shown only if expanded */}
              {isExpanded && (
                <>
                  <View style={styles.cardDivider} />
                  
                  {/* Scan Image */}
                  <View style={styles.imageContainer}>
                    {imageFailed ? (
                      <View style={styles.imagePlaceholder}>
                        <Ionicons name="image-outline" size={36} color="rgba(255,255,255,0.4)" />
                        <Text style={styles.imagePlaceholderText}>Preview Unavailable</Text>
                        <Text style={styles.imagePlaceholderSub}>
                          DICOM image stored securely on hospital servers for patient privacy
                        </Text>
                      </View>
                    ) : (
                      <RNImage
                        source={{ uri: fullImageUrl }}
                        style={styles.scanImage}
                        resizeMode="cover"
                        onError={() => handleImageError(item.id)}
                      />
                    )}
                  </View>

                  {/* Clinical section */}
                  {renderClinicalSection(item)}

                  {/* Download PDF Report Button */}
                  <View style={styles.actionRow}>
                    <TouchableOpacity
                      style={[styles.actionBtn, styles.downloadBtn, isGenerating && styles.downloadBtnLoading]}
                      onPress={() => handleDownloadPDF(item)}
                      activeOpacity={0.85}
                      disabled={isGenerating}
                    >
                      {isGenerating ? (
                        <ActivityIndicator size="small" color="#ffffff" />
                      ) : (
                        <Ionicons name="document-text-outline" size={16} color="#ffffff" />
                      )}
                      <Text style={styles.downloadBtnText}>
                        {isGenerating ? 'Generating Report...' : 'Download PDF Report'}
                      </Text>
                    </TouchableOpacity>
                  </View>
                </>
              )}
            </View>
          );
        }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F4F6FA',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingBottom: 16,
    backgroundColor: '#ffffff',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
  },
  backButton: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: '#F1F5F9',
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#0F172A',
    textAlign: 'center',
  },
  listContainer: {
    padding: 16,
    paddingTop: 16,
    gap: 16,
    paddingBottom: 40,
  },
  card: {
    backgroundColor: '#ffffff',
    borderRadius: 16,
    overflow: 'hidden',
    ...orvellaShadow.md,
    borderWidth: 1,
    borderColor: '#E2E8F5',
    borderLeftWidth: 5,
    borderLeftColor: orvellaColors.primary,
  },
  cardHeaderInteractive: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 16,
    gap: 8,
  },
  headerRightCol: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  chevronBox: {
    width: 26,
    height: 26,
    borderRadius: 13,
    backgroundColor: '#F1F5F9',
    justifyContent: 'center',
    alignItems: 'center',
  },
  collapsedSummary: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: '#F8FAFC',
    borderTopWidth: 1,
    borderTopColor: '#E2E8F5',
  },
  collapsedSummaryText: {
    fontSize: 11,
    color: orvellaColors.textSecondary,
  },
  collapsedAttendingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  viewDetailsLink: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: orvellaColors.primaryLight,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 20,
  },
  viewDetailsLinkText: {
    fontSize: 11,
    color: orvellaColors.primary,
    fontWeight: '700',
  },
  scanTypeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    flex: 1,
  },
  scanIconBox: {
    width: 40,
    height: 40,
    borderRadius: 10,
    backgroundColor: '#E8F0FE',
    justifyContent: 'center',
    alignItems: 'center',
    flexShrink: 0,
  },
  scanTypeTitle: {
    fontSize: 14,
    fontWeight: '700',
    color: '#1A2340',
  },
  scanMeta: {
    fontSize: 11,
    color: orvellaColors.textSecondary,
    marginTop: 2,
  },
  cardDivider: {
    height: 1,
    backgroundColor: '#E2E8F5',
    marginHorizontal: 16,
  },
  imageContainer: {
    height: 200,
    backgroundColor: '#0c0d12',
    marginHorizontal: 16,
    marginTop: 16,
    borderRadius: 12,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: '#E2E8F5',
    ...orvellaShadow.sm,
  },
  scanImage: {
    width: '100%',
    height: '100%',
  },
  imagePlaceholder: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: orvellaSpacing.md,
    gap: 6,
  },
  imagePlaceholderText: {
    fontSize: 13,
    fontWeight: '600',
    color: 'rgba(255,255,255,0.7)',
  },
  imagePlaceholderSub: {
    fontSize: 11,
    color: 'rgba(255,255,255,0.4)',
    textAlign: 'center',
    lineHeight: 15,
    paddingHorizontal: orvellaSpacing.md,
  },
  clinicalCard: {
    marginHorizontal: 16,
    marginVertical: 16,
    backgroundColor: '#ffffff',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#E0E7FF',
    overflow: 'hidden',
    ...orvellaShadow.sm,
  },
  clinicalCardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 12,
    paddingHorizontal: 14,
    backgroundColor: '#F5F7FF',
    borderBottomWidth: 1,
    borderBottomColor: '#E0E7FF',
    gap: 10,
  },
  clinicalTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    flex: 1,
    minWidth: 0,
  },
  clinicalIconBox: {
    width: 32,
    height: 32,
    borderRadius: 8,
    backgroundColor: '#ECFDF5',
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#A7F3D0',
    flexShrink: 0,
  },
  clinicalTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: '#1A2340',
  },
  clinicalSubtitle: {
    fontSize: 10,
    color: orvellaColors.textSecondary,
    marginTop: 1,
  },
  verifiedBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#16A34A',
    paddingHorizontal: 9,
    paddingVertical: 5,
    borderRadius: 6,
    flexShrink: 0,
  },
  verifiedBadgeText: {
    fontSize: 9,
    fontWeight: '700',
    color: '#ffffff',
    letterSpacing: 0.5,
  },
  notesBox: {
    padding: 14,
    borderLeftWidth: 3,
    borderLeftColor: orvellaColors.primary,
    backgroundColor: '#F8FAFC',
    marginHorizontal: 14,
    marginVertical: 14,
    borderRadius: 8,
  },
  notesQuote: {
    fontSize: 13,
    color: '#374151',
    fontStyle: 'italic',
    lineHeight: 20,
  },
  prescriptionContainer: {
    paddingHorizontal: 14,
    paddingBottom: 14,
    gap: 10,
  },
  nextCheckupCard: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#EEF3FF',
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#C7D2FE',
  },
  nextCheckupLeft: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  nextCheckupLabel: {
    fontSize: 10,
    fontWeight: '600',
    color: orvellaColors.primary,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  nextCheckupValue: {
    fontSize: 13,
    fontWeight: '700',
    color: '#1A2340',
    marginTop: 2,
  },
  prescGrid: {
    gap: 10,
  },
  prescItem: {
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
    ...orvellaShadow.sm,
  },
  prescFullItem: {
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
    ...orvellaShadow.sm,
  },
  prescItemHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 6,
  },
  prescItemTitle: {
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 0.3,
  },
  prescItemText: {
    fontSize: 13,
    color: '#374151',
    lineHeight: 18,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    paddingVertical: 6,
  },
  infoIconBox: {
    width: 28,
    height: 28,
    borderRadius: 8,
    backgroundColor: '#F1F5F9',
    justifyContent: 'center',
    alignItems: 'center',
  },
  infoContent: {
    flex: 1,
  },
  infoLabel: {
    fontSize: 10,
    color: orvellaColors.textSecondary,
    textTransform: 'uppercase',
    letterSpacing: 0.4,
    fontWeight: '600',
  },
  infoValue: {
    fontSize: 13,
    color: '#1A2340',
    fontWeight: '600',
    marginTop: 1,
  },
  clinicalFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderTopWidth: 1,
    borderTopColor: '#E2E8F5',
    backgroundColor: '#F5F7FF',
  },
  clinicalDoctorText: {
    fontSize: 11,
    color: orvellaColors.textSecondary,
    fontWeight: '500',
  },
  actionRow: {
    marginHorizontal: 16,
    marginBottom: 16,
  },
  actionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    paddingHorizontal: 16,
    paddingVertical: 13,
    borderRadius: 12,
  },
  downloadBtn: {
    backgroundColor: orvellaColors.primary,
    ...orvellaShadow.sm,
  },
  downloadBtnLoading: {
    backgroundColor: orvellaColors.primary + 'CC',
  },
  downloadBtnText: {
    fontSize: 13,
    fontWeight: '700',
    color: '#ffffff',
  },
});
