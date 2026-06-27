import React, { useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, ActivityIndicator } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useScans } from '../../hooks/useApi';
import { orvellaColors, orvellaSpacing, orvellaShadow } from '../../constants/orvella';
import { router } from 'expo-router';

export default function NotificationsScreen() {
  const { data: scansData, isLoading } = useScans();
  const insets = useSafeAreaInsets();
  const [activeTab, setActiveTab] = useState<'reupload' | 'pending'>('reupload');
  
  const scans = scansData?.data || [];
  
  // Re-upload (Action Required) scans
  const rejectedScans = scans.filter((s: any) => s.status === 'rejected');

  // Helper to calculate hours elapsed
  const getElapsedHours = (dateString: string) => {
    const diffMs = Date.now() - new Date(dateString).getTime();
    return diffMs / (1000 * 60 * 60);
  };

  // Pending scans (> 24 hours, sorted oldest first)
  const pendingScans = scans
    .filter((s: any) => s.status !== 'approved' && s.status !== 'rejected' && getElapsedHours(s.created_at) >= 24)
    .sort((a: any, b: any) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime());

  return (
    <ScrollView style={styles.container} contentContainerStyle={[styles.contentContainer, { paddingTop: Math.max(insets.top, 16) }]}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
          <Ionicons name="arrow-back" size={24} color="#1A2340" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Notifications</Text>
      </View>

      {/* Tabs */}
      <View style={styles.tabBar}>
        <TouchableOpacity 
          style={[styles.tabButton, activeTab === 'reupload' && styles.activeTabButton]} 
          onPress={() => setActiveTab('reupload')}
          activeOpacity={0.8}
        >
          <Text style={[styles.tabButtonText, activeTab === 'reupload' && styles.activeTabButtonText]}>
            Action Required
          </Text>
          {rejectedScans.length > 0 && (
            <View style={[styles.tabBadge, { backgroundColor: orvellaColors.danger }]}>
              <Text style={styles.tabBadgeText}>{rejectedScans.length}</Text>
            </View>
          )}
        </TouchableOpacity>

        <TouchableOpacity 
          style={[styles.tabButton, activeTab === 'pending' && styles.activeTabButton]} 
          onPress={() => setActiveTab('pending')}
          activeOpacity={0.8}
        >
          <Text style={[styles.tabButtonText, activeTab === 'pending' && styles.activeTabButtonText]}>
            Pending Reviews
          </Text>
          {pendingScans.length > 0 && (
            <View style={[styles.tabBadge, { backgroundColor: orvellaColors.warning }]}>
              <Text style={styles.tabBadgeText}>{pendingScans.length}</Text>
            </View>
          )}
        </TouchableOpacity>
      </View>

      {isLoading ? (
        <ActivityIndicator size="large" color={orvellaColors.primary} style={{ marginTop: 40 }} />
      ) : activeTab === 'reupload' ? (
        rejectedScans.length > 0 ? (
          <>
            <Text style={styles.sectionTitle}>Action Required</Text>
            {rejectedScans.map((scan: any) => {
              let rejectionNotes = '';
              if (scan.diagnosis?.notes) {
                try {
                  const parsed = JSON.parse(scan.diagnosis.notes);
                  rejectionNotes = parsed.medical_notes || scan.diagnosis.notes;
                } catch {
                  rejectionNotes = scan.diagnosis.notes;
                }
              }

              return (
                <View key={scan.id} style={styles.rejectedCard}>
                  <View style={styles.rejectedCardHeader}>
                    <View style={styles.rejectedIconBox}>
                      <Ionicons name="alert-circle" size={24} color={orvellaColors.danger} />
                    </View>
                    <View style={styles.rejectedPatientInfo}>
                      <Text style={styles.rejectedPatientName} numberOfLines={1}>
                        {scan.patient?.name || `Patient ID: ${scan.patient_id}`}
                      </Text>
                      <Text style={styles.rejectedDoctorText} numberOfLines={1}>
                        {scan.doctor?.full_name ? `Dr. ${scan.doctor.full_name}` : 'Unassigned'}
                      </Text>
                    </View>
                    <TouchableOpacity
                      style={styles.reuploadBtn}
                      activeOpacity={0.85}
                      onPress={() => router.push({
                        pathname: '/(medrec)/upload',
                        params: { patientId: scan.patient_id, doctorId: scan.doctor_id }
                      })}
                    >
                      <Text style={styles.reuploadBtnText}>Re-upload</Text>
                    </TouchableOpacity>
                  </View>
                  {rejectionNotes ? (
                    <View style={styles.rejectionNoteBubble}>
                      <Text style={styles.rejectionNoteText}>Note: "{rejectionNotes}"</Text>
                    </View>
                  ) : null}
                </View>
              );
            })}
          </>
        ) : (
          <View style={styles.emptyState}>
            <Ionicons name="checkmark-circle-outline" size={48} color={orvellaColors.success || '#10B981'} />
            <Text style={styles.emptyStateTitle}>All Caught Up!</Text>
            <Text style={styles.emptyStateDesc}>You have no pending notifications or rejected scans to action.</Text>
          </View>
        )
      ) : (
        pendingScans.length > 0 ? (
          <>
            <Text style={styles.sectionTitleWarning}>{"Unreviewed for > 24 Hours"}</Text>
            {pendingScans.map((scan: any) => {
              const hours = Math.round(getElapsedHours(scan.created_at));
              return (
                <View key={scan.id} style={styles.pendingCard}>
                  <View style={styles.pendingCardHeader}>
                    <View style={styles.pendingIconBox}>
                      <Ionicons name="time" size={24} color={orvellaColors.warning} />
                    </View>
                    <View style={styles.rejectedPatientInfo}>
                      <Text style={styles.pendingPatientName} numberOfLines={1}>
                        {scan.patient?.name || `Patient ID: ${scan.patient_id}`}
                      </Text>
                      <Text style={styles.rejectedDoctorText} numberOfLines={1}>
                        {scan.doctor?.full_name ? `Dr. ${scan.doctor.full_name}` : 'Unassigned'}
                      </Text>
                    </View>
                    <View style={styles.delayBadge}>
                      <Text style={styles.delayBadgeText}>{hours}h pending</Text>
                    </View>
                  </View>
                </View>
              );
            })}
          </>
        ) : (
          <View style={styles.emptyState}>
            <Ionicons name="time-outline" size={48} color={orvellaColors.primary} />
            <Text style={styles.emptyStateTitle}>No Delayed Reviews</Text>
            <Text style={styles.emptyStateDesc}>All pending scans are either reviewed or have been uploaded in the last 24 hours.</Text>
          </View>
        )
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F4F6FA',
  },
  contentContainer: {
    padding: 16,
    paddingBottom: 40,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 20,
  },
  backBtn: {
    padding: 8,
    marginRight: 8,
  },
  headerTitle: {
    fontSize: 22,
    fontWeight: '800',
    color: '#1A2340',
  },
  tabBar: {
    flexDirection: 'row',
    backgroundColor: '#EAEFF8',
    borderRadius: 12,
    padding: 4,
    marginBottom: 20,
  },
  tabButton: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 10,
    borderRadius: 10,
    gap: 6,
  },
  activeTabButton: {
    backgroundColor: '#ffffff',
    ...orvellaShadow.sm,
  },
  tabButtonText: {
    fontSize: 13,
    fontWeight: '600',
    color: '#556987',
  },
  activeTabButtonText: {
    fontWeight: '800',
    color: '#1A2340',
  },
  tabBadge: {
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 8,
  },
  tabBadgeText: {
    color: '#ffffff',
    fontSize: 9,
    fontWeight: 'bold',
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: '700',
    color: orvellaColors.danger,
    marginBottom: 12,
  },
  sectionTitleWarning: {
    fontSize: 14,
    fontWeight: '700',
    color: orvellaColors.warning,
    marginBottom: 12,
  },
  rejectedCard: {
    backgroundColor: '#ffffff',
    borderLeftColor: orvellaColors.danger,
    borderLeftWidth: 3,
    borderRadius: 12,
    padding: 12,
    marginBottom: 12,
    ...orvellaShadow.sm,
  },
  pendingCard: {
    backgroundColor: '#ffffff',
    borderLeftColor: orvellaColors.warning,
    borderLeftWidth: 3,
    borderRadius: 12,
    padding: 12,
    marginBottom: 12,
    ...orvellaShadow.sm,
  },
  rejectedCardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  pendingCardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  rejectedIconBox: {
    marginRight: 12,
    justifyContent: 'center',
    alignItems: 'center',
  },
  pendingIconBox: {
    marginRight: 12,
    justifyContent: 'center',
    alignItems: 'center',
  },
  rejectedPatientInfo: {
    flex: 1,
  },
  rejectedPatientName: {
    fontSize: 14,
    fontWeight: '700',
    color: '#8A2525',
  },
  pendingPatientName: {
    fontSize: 14,
    fontWeight: '700',
    color: '#1A2340',
  },
  rejectedDoctorText: {
    fontSize: 11,
    color: '#9CA3AF',
  },
  reuploadBtn: {
    backgroundColor: orvellaColors.danger,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
  },
  reuploadBtnText: {
    color: '#ffffff',
    fontSize: 10,
    fontWeight: 'bold',
  },
  delayBadge: {
    backgroundColor: '#FFFBEB',
    borderColor: '#FDE68A',
    borderWidth: 1,
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  delayBadgeText: {
    color: orvellaColors.warning,
    fontSize: 10,
    fontWeight: 'bold',
  },
  rejectionNoteBubble: {
    backgroundColor: '#F9F9F9',
    borderRadius: 8,
    padding: 8,
    marginLeft: 36,
    borderWidth: 1,
    borderColor: '#F3F4F6',
  },
  rejectionNoteText: {
    fontSize: 11,
    color: '#374151',
    fontStyle: 'italic',
  },
  emptyState: {
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 60,
  },
  emptyStateTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1A2340',
    marginTop: 16,
    marginBottom: 8,
  },
  emptyStateDesc: {
    fontSize: 14,
    color: orvellaColors.textSecondary,
    textAlign: 'center',
    paddingHorizontal: 32,
  },
});
