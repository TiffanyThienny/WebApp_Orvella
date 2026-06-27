import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  TouchableOpacity,
  ActivityIndicator,
  Modal,
  Image,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '../../store/auth.store';
import { usePatients, useScans } from '../../hooks/useApi';
import {
  orvellaColors,
  orvellaFontSize,
  orvellaSpacing,
  orvellaRadius,
  orvellaShadow,
  statusColors,
} from '../../constants/orvella';
import { Card } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { router } from 'expo-router';
import { BASE_URL } from '../../api/client';

export default function MedrecDashboard() {
  const { user, logout } = useAuthStore();
  const insets = useSafeAreaInsets();
  const { data: patients, isLoading: loadingPatients, refetch: refetchPatients } = usePatients();
  const { data: scansData, isLoading: loadingScans, refetch: refetchScans } = useScans();
  const [selectedScan, setSelectedScan] = useState<any>(null);
  const [isModalVisible, setIsModalVisible] = useState(false);

  const handleRefresh = async () => {
    await Promise.all([refetchPatients(), refetchScans()]);
  };

  const isRefreshing = loadingPatients || loadingScans;

  const scans = scansData?.data || [];
  const pendingReviewCount = scans.filter((s: any) => s.status === 'pending_review').length;
  const rejectedScans = scans.filter((s: any) => s.status === 'rejected');
  const recentScans = scans.filter((s: any) => s.status !== 'rejected').slice(0, 3);

  const getFullImageUrl = (url: string) => {
    if (!url) return '';
    if (url.startsWith('http://') || url.startsWith('https://')) return url;
    return `${BASE_URL}/${url.startsWith('/') ? url.slice(1) : url}`;
  };

  return (
    <View style={{ flex: 1 }}>
      <ScrollView
        style={styles.container}
        contentContainerStyle={styles.contentContainer}
        refreshControl={
          <RefreshControl
            refreshing={isRefreshing}
            onRefresh={handleRefresh}
            colors={[orvellaColors.primary]}
          />
        }
      >
        {/* 1. Header with Medrec Info */}
        <View style={[styles.topAppBar, { paddingTop: Math.max(insets.top, 16) }]}>
          <View style={styles.appBarLeft}>
            <Text style={styles.appBarTitle}>Dashboard</Text>
          </View>
          <View style={styles.appBarRight}>
            <TouchableOpacity 
              style={styles.notifIconContainer} 
              onPress={() => router.push('/(medrec)/notifications')}
            >
              <Ionicons name="notifications-outline" size={24} color="#1A2340" />
              {rejectedScans.length > 0 && (
                <View style={styles.notifBadge}>
                  <Text style={styles.notifBadgeText}>{rejectedScans.length}</Text>
                </View>
              )}
            </TouchableOpacity>
            <View style={styles.avatarMini}>
              <Text style={styles.avatarMiniText}>{user?.full_name ? user.full_name.charAt(0).toUpperCase() : 'U'}</Text>
            </View>
          </View>
        </View>

        {/* 2. Welcome Banner */}
        <View style={styles.welcomeBanner}>
          <View style={styles.bannerContent}>
            <View style={styles.bannerTextContainer}>
              <Text style={styles.bannerTitle}>Orvella MedRec</Text>
              <Text style={styles.bannerDesc}>
                Clinical data management & scan tracking
              </Text>
            </View>
            <Ionicons name="shield-checkmark" size={36} color="rgba(255,255,255,0.15)" style={{ position: 'absolute', right: -5, top: -5 }} />
          </View>
        </View>

        {/* 3. Real-time Clinical Statistics Grid */}
        <Text style={styles.sectionTitle}>Overview</Text>
        <View style={styles.statsGrid}>
          {/* Total Patients */}
          <View style={styles.statsCard}>
            <View style={[styles.statsIconBox, { backgroundColor: '#EEF3FF' }]}>
              <Ionicons name="people" size={16} color={orvellaColors.primary} />
            </View>
            <View style={styles.statsTextWrap}>
              {loadingPatients ? (
                <ActivityIndicator size="small" color={orvellaColors.primary} />
              ) : (
                <Text style={styles.statsValue}>{patients?.length || 0}</Text>
              )}
              <Text style={styles.statsLabel}>Total Patients</Text>
            </View>
          </View>

          {/* Total Scans */}
          <View style={styles.statsCard}>
            <View style={[styles.statsIconBox, { backgroundColor: '#F0FDF4' }]}>
              <Ionicons name="scan" size={16} color="#16A34A" />
            </View>
            <View style={styles.statsTextWrap}>
              {loadingScans ? (
                <ActivityIndicator size="small" color="#16A34A" />
              ) : (
                <Text style={styles.statsValue}>{scans.length}</Text>
              )}
              <Text style={styles.statsLabel}>Scans Uploaded</Text>
            </View>
          </View>

          {/* Pending Specialist Review */}
          <View style={styles.statsCard}>
            <View style={[styles.statsIconBox, { backgroundColor: '#FFFBEB' }]}>
              <Ionicons name="time" size={16} color={orvellaColors.warning} />
            </View>
            <View style={styles.statsTextWrap}>
              {loadingScans ? (
                <ActivityIndicator size="small" color={orvellaColors.warning} />
              ) : (
                <Text style={[styles.statsValue, pendingReviewCount > 0 && { color: orvellaColors.warning }]}>
                  {pendingReviewCount}
                </Text>
              )}
              <Text style={styles.statsLabel}>Pending Review</Text>
            </View>
          </View>

          {/* Rejected Scans */}
          <View style={styles.statsCard}>
            <View style={[styles.statsIconBox, { backgroundColor: '#FFF1F2' }]}>
              <Ionicons name="close-circle" size={16} color={orvellaColors.danger} />
            </View>
            <View style={styles.statsTextWrap}>
              {loadingScans ? (
                <ActivityIndicator size="small" color={orvellaColors.danger} />
              ) : (
                <Text style={[styles.statsValue, rejectedScans.length > 0 && { color: orvellaColors.danger }]}>
                  {rejectedScans.length}
                </Text>
              )}
              <Text style={styles.statsLabel}>Rejected Scans</Text>
            </View>
          </View>
        </View>

        {/* 4. Quick Workflow Actions */}
        <Text style={styles.sectionTitle}>Medical Workflow</Text>
        <View style={styles.quickActionsGrid}>
          {/* Upload Scan */}
          <TouchableOpacity style={styles.actionCard} onPress={() => router.push('/(medrec)/upload')} activeOpacity={0.8}>
            <View style={[styles.actionIconContainer, { backgroundColor: '#EEF3FF' }]}>
              <Ionicons name="cloud-upload" size={18} color={orvellaColors.primary} />
            </View>
            <View style={styles.actionTextGroup}>
              <Text style={styles.actionText}>Upload New Scan</Text>
              <Text style={styles.actionDesc}>Upload CT Scan & vitals</Text>
            </View>
            <Ionicons name="chevron-forward" size={16} color={orvellaColors.textMuted} />
          </TouchableOpacity>

          {/* Scan Management */}
          <TouchableOpacity style={styles.actionCard} onPress={() => router.push('/(medrec)/scans')} activeOpacity={0.8}>
            <View style={[styles.actionIconContainer, { backgroundColor: '#F0FDFA' }]}>
              <Ionicons name="layers" size={18} color={orvellaColors.accent} />
            </View>
            <View style={styles.actionTextGroup}>
              <Text style={styles.actionText}>Scan Management</Text>
              <Text style={styles.actionDesc}>Track scan status</Text>
            </View>
            <Ionicons name="chevron-forward" size={16} color={orvellaColors.textMuted} />
          </TouchableOpacity>

          {/* Patient Directory */}
          <TouchableOpacity style={styles.actionCard} onPress={() => router.push('/(medrec)/patients')} activeOpacity={0.8}>
            <View style={[styles.actionIconContainer, { backgroundColor: '#F0FDF4' }]}>
              <Ionicons name="people" size={18} color="#16A34A" />
            </View>
            <View style={styles.actionTextGroup}>
              <Text style={styles.actionText}>Patient Directory</Text>
              <Text style={styles.actionDesc}>View health records</Text>
            </View>
            <Ionicons name="chevron-forward" size={16} color={orvellaColors.textMuted} />
          </TouchableOpacity>
        </View>

        {/* 5. Recent Scan Activities */}
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Recent CT Scan Uploads</Text>
          {scans.length > 0 && (
            <TouchableOpacity onPress={() => router.push('/(medrec)/scans')}>
              <Text style={styles.sectionActionText}>View All</Text>
            </TouchableOpacity>
          )}
        </View>

        {recentScans.length > 0 ? (
          recentScans.map((scan: any) => (
            <TouchableOpacity 
              key={scan.id} 
              style={styles.activityCard}
              onPress={() => {
                setSelectedScan(scan);
                setIsModalVisible(true);
              }}
              activeOpacity={0.8}
            >
              <View style={styles.activityAvatar}>
                <Ionicons name="document-text" size={16} color={orvellaColors.primary} />
              </View>
              <View style={styles.activityContent}>
                <Text style={styles.patientName}>{scan.patient?.name || `Patient ID: ${scan.patient_id}`}</Text>
                <Text style={styles.uploadTime}>
                  {new Date(scan.created_at).toLocaleDateString('en-US', {
                    day: 'numeric',
                    month: 'short',
                    hour: '2-digit',
                    minute: '2-digit',
                  })}
                </Text>
              </View>
              <View style={styles.activityRight}>
                <StatusBadge status={scan.status} />
                <Text style={styles.doctorName} numberOfLines={1}>
                  {scan.doctor?.full_name ? `Dr. ${scan.doctor.full_name}` : 'Unassigned'}
                </Text>
              </View>
            </TouchableOpacity>
          ))
        ) : (
          <Card style={styles.emptyCard} variant="outlined">
            <Ionicons name="document-text-outline" size={28} color={orvellaColors.textMuted} />
            <Text style={styles.emptyText}>No CT Scan files have been uploaded yet.</Text>
            <Button
              title="Upload First Scan"
              size="sm"
              style={styles.emptyActionBtn}
              onPress={() => router.push('/(medrec)/upload')}
            />
          </Card>
        )}
      </ScrollView>

      {/* Detail Modal */}
      <Modal
        animationType="slide"
        transparent={true}
        visible={isModalVisible}
        onRequestClose={() => setIsModalVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalTitle}>CT Scan Detail</Text>
              <TouchableOpacity onPress={() => setIsModalVisible(false)}>
                <Ionicons name="close" size={24} color="#1A2340" />
              </TouchableOpacity>
            </View>

            {selectedScan && (
              <ScrollView style={styles.modalBody} showsVerticalScrollIndicator={false}>
                {selectedScan.image_url ? (
                  <Image 
                    source={{ uri: getFullImageUrl(selectedScan.image_url) }} 
                    style={styles.modalImage} 
                    resizeMode="cover" 
                  />
                ) : (
                  <View style={styles.modalImagePlaceholder}>
                    <Ionicons name="scan-outline" size={48} color={orvellaColors.textMuted} />
                    <Text style={{ color: orvellaColors.textMuted, marginTop: 8 }}>No Image Available</Text>
                  </View>
                )}

                <View style={styles.modalDetails}>
                  <View style={styles.modalInfoRow}>
                    <Text style={styles.modalInfoLabel}>Scan ID</Text>
                    <Text style={styles.modalInfoValue}>#{selectedScan.id}</Text>
                  </View>

                  <View style={styles.modalInfoRow}>
                    <Text style={styles.modalInfoLabel}>Patient Name</Text>
                    <Text style={styles.modalInfoValue}>
                      {selectedScan.patient?.name || `Patient ID: ${selectedScan.patient_id}`}
                    </Text>
                  </View>

                  <View style={styles.modalInfoRow}>
                    <Text style={styles.modalInfoLabel}>Assigned Doctor</Text>
                    <Text style={styles.modalInfoValue}>
                      {selectedScan.doctor?.full_name ? `Dr. ${selectedScan.doctor.full_name}` : 'Unassigned'}
                    </Text>
                  </View>

                  <View style={styles.modalInfoRow}>
                    <Text style={styles.modalInfoLabel}>Upload Date</Text>
                    <Text style={styles.modalInfoValue}>
                      {new Date(selectedScan.created_at).toLocaleString('en-US', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit',
                      })}
                    </Text>
                  </View>

                  <View style={styles.modalInfoRow}>
                    <Text style={styles.modalInfoLabel}>Status</Text>
                    <StatusBadge status={selectedScan.status} />
                  </View>

                  {/* AI results hidden from MedRec staff */}
                </View>
              </ScrollView>
            )}

            <Button 
              title="Close" 
              onPress={() => setIsModalVisible(false)} 
              style={{ marginTop: 16 }}
            />
          </View>
        </View>
      </Modal>
    </View>
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
  topAppBar: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  appBarTitle: {
    fontSize: 22,
    fontWeight: '800',
    color: '#1A2340',
  },
  appBarLeft: {
    flex: 1,
  },
  appBarRight: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 16,
  },
  notifIconContainer: {
    position: 'relative',
    justifyContent: 'center',
    alignItems: 'center',
  },
  notifBadge: {
    position: 'absolute',
    top: -4,
    right: -4,
    backgroundColor: orvellaColors.danger,
    width: 16,
    height: 16,
    borderRadius: 8,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1.5,
    borderColor: '#F4F6FA',
  },
  notifBadgeText: {
    color: '#ffffff',
    fontSize: 9,
    fontWeight: 'bold',
  },
  avatarMini: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: orvellaColors.primary,
    justifyContent: 'center',
    alignItems: 'center',
    ...orvellaShadow.sm,
  },
  avatarMiniText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: 'bold',
  },
  welcomeBanner: {
    backgroundColor: orvellaColors.primary,
    borderRadius: 16,
    padding: 16,
    marginBottom: 16,
    ...orvellaShadow.sm,
    overflow: 'hidden',
  },
  bannerContent: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    position: 'relative',
  },
  bannerTextContainer: {
    flex: 1,
    paddingRight: 8,
  },
  bannerTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#ffffff',
    marginBottom: 4,
  },
  bannerDesc: {
    fontSize: 11,
    color: 'rgba(255,255,255,0.85)',
    lineHeight: 14,
  },
  alertBanner: {
    flexDirection: 'row',
    backgroundColor: '#FFF1F2',
    borderRadius: 12,
    padding: 12,
    marginBottom: 16,
    alignItems: 'center',
    gap: 8,
  },
  alertBannerText: {
    fontSize: 12,
    color: '#8A2525',
    flex: 1,
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: '700',
    color: '#1A2340',
    marginBottom: 8,
    letterSpacing: 0.1,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 16,
    marginBottom: 8,
  },
  sectionActionText: {
    fontSize: 12,
    fontWeight: 'bold',
    color: orvellaColors.primary,
  },
  statsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
    marginBottom: 16,
  },
  statsCard: {
    width: '48%',
    flexDirection: 'row',
    alignItems: 'center',
    padding: 10,
    backgroundColor: '#ffffff',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#EAECF0',
    ...orvellaShadow.sm,
    gap: 10,
  },
  statsIconBox: {
    width: 32,
    height: 32,
    borderRadius: 8,
    justifyContent: 'center',
    alignItems: 'center',
  },
  statsTextWrap: {
    flex: 1,
  },
  statsValue: {
    fontSize: 18,
    fontWeight: '800',
    color: '#1A2340',
  },
  statsLabel: {
    fontSize: 10,
    color: orvellaColors.textSecondary,
    marginTop: 2,
  },
  quickActionsGrid: {
    flexDirection: 'column',
    gap: 8,
    marginBottom: 16,
  },
  actionCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderRadius: 12,
    paddingVertical: 12,
    paddingHorizontal: 12,
    borderWidth: 1,
    borderColor: '#EAECF0',
    ...orvellaShadow.sm,
    gap: 12,
  },
  actionIconContainer: {
    width: 36,
    height: 36,
    borderRadius: 10,
    justifyContent: 'center',
    alignItems: 'center',
  },
  actionTextGroup: {
    flex: 1,
  },
  actionText: {
    fontSize: 14,
    fontWeight: '700',
    color: '#1A2340',
  },
  actionDesc: {
    fontSize: 11,
    color: orvellaColors.textSecondary,
    marginTop: 2,
  },
  activityCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    marginBottom: 8,
    backgroundColor: '#ffffff',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#EAECF0',
    gap: 10,
  },
  activityAvatar: {
    width: 36,
    height: 36,
    borderRadius: 8,
    backgroundColor: '#EEF3FF',
    justifyContent: 'center',
    alignItems: 'center',
  },
  activityContent: {
    flex: 1,
  },
  patientName: {
    fontSize: 13,
    fontWeight: '700',
    color: '#1A2340',
  },
  uploadTime: {
    fontSize: 10,
    color: orvellaColors.textMuted,
    marginTop: 2,
  },
  activityRight: {
    alignItems: 'flex-end',
    width: 100,
  },
  doctorName: {
    fontSize: 10,
    color: orvellaColors.textSecondary,
    marginTop: 4,
  },
  emptyCard: {
    padding: 16,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#ffffff',
    borderRadius: 12,
    gap: 8,
  },
  emptyText: {
    fontSize: 12,
    color: orvellaColors.textSecondary,
    textAlign: 'center',
  },
  emptyActionBtn: {
    marginTop: 8,
    width: '100%',
  },
  urgentBadge: {
    backgroundColor: orvellaColors.danger,
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  urgentBadgeText: {
    color: '#ffffff',
    fontSize: 9,
    fontWeight: 'bold',
  },
  rejectedCard: {
    backgroundColor: '#FFF1F2',
    borderRadius: 12,
    padding: 12,
    marginBottom: 8,
  },
  rejectedCardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  rejectedIconBox: {
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
  rejectionNoteBubble: {
    backgroundColor: 'rgba(255, 255, 255, 0.6)',
    borderRadius: 8,
    padding: 8,
    marginLeft: 36, // Align with text, skipping the icon
  },
  rejectionNoteText: {
    fontSize: 11,
    color: '#8A2525',
    fontStyle: 'italic',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#ffffff',
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    padding: 20,
    maxHeight: '85%',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#EAECF0',
    paddingBottom: 12,
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#1A2340',
  },
  modalBody: {
    marginBottom: 16,
  },
  modalImage: {
    width: '100%',
    height: 200,
    borderRadius: 12,
    marginBottom: 16,
    backgroundColor: '#000000',
  },
  modalImagePlaceholder: {
    width: '100%',
    height: 200,
    borderRadius: 12,
    marginBottom: 16,
    backgroundColor: '#F4F6FA',
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#EAECF0',
    borderStyle: 'dashed',
  },
  modalDetails: {
    gap: 12,
  },
  modalInfoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 4,
  },
  modalInfoLabel: {
    fontSize: 13,
    color: orvellaColors.textSecondary,
    fontWeight: '600',
  },
  modalInfoValue: {
    fontSize: 13,
    color: '#1A2340',
    fontWeight: '700',
  },
  aiResultSection: {
    marginTop: 16,
    borderTopWidth: 1,
    borderTopColor: '#EAECF0',
    paddingTop: 16,
  },
  aiResultTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: '#1A2340',
    marginBottom: 8,
  },
  aiResultBox: {
    backgroundColor: '#F0F5FF',
    borderRadius: 12,
    padding: 12,
    borderWidth: 1,
    borderColor: '#D0E2FF',
    gap: 8,
  },
});
