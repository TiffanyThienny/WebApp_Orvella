import React from 'react';
import {
  FlatList,
  StyleSheet,
  Text,
  View,
  Image,
  TouchableOpacity,
  ScrollView,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useScans } from '../../hooks/useApi';
import { useAuthStore } from '../../store/auth.store';
import { useLocalSearchParams, router } from 'expo-router';
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

import { BASE_URL } from '../../api/client';

const STATUS_CONFIG: Record<string, { icon: string; color: string; bg: string }> = {
  pending: { icon: 'time-outline', color: '#D97706', bg: '#FFFBEB' },
  pending_review: { icon: 'hourglass-outline', color: '#7C3AED', bg: '#F5F3FF' },
  analyzed: { icon: 'pulse-outline', color: '#1A73E8', bg: '#EEF3FF' },
  approved: { icon: 'checkmark-circle-outline', color: '#059669', bg: '#ECFDF5' },
  rejected: { icon: 'close-circle-outline', color: '#DC2626', bg: '#FEF2F2' },
  completed: { icon: 'checkmark-done-outline', color: '#059669', bg: '#ECFDF5' },
  ai_processing: { icon: 'sync-outline', color: '#D97706', bg: '#FFFBEB' },
};

function getStatusConfig(status: string) {
  return STATUS_CONFIG[status] || { icon: 'help-circle-outline', color: '#9BA8BB', bg: '#F4F6FA' };
}

const getFullImageUrl = (url: string) => {
  if (!url) return '';
  if (url.startsWith('http://') || url.startsWith('https://')) return url;
  return `${BASE_URL}/${url.startsWith('/') ? url.slice(1) : url}`;
};

export default function MedrecScansScreen() {
  const { user } = useAuthStore();
  const insets = useSafeAreaInsets();
  const { data, isLoading, refetch } = useScans();
  const [statusFilter, setStatusFilter] = React.useState<'all' | 'pending' | 'approved' | 'rejected'>('all');
  const { patientName } = useLocalSearchParams<{ patientName?: string }>();
  const [activePatientFilter, setActivePatientFilter] = React.useState<string | null>(null);

  React.useEffect(() => {
    if (patientName) {
      setActivePatientFilter(patientName);
      // Clear the query param so navigating back/refreshing doesn't lock it
      router.setParams({ patientName: undefined });
    }
  }, [patientName]);
  
  const scans = data?.data || [];

  const processedScans = React.useMemo(() => {
    let arr = [...scans];
    
    // Default sort by newest first
    arr.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());

    // Patient filter
    if (activePatientFilter) {
      arr = arr.filter((s: any) => 
        (s.patient_name || '').toLowerCase().includes(activePatientFilter.toLowerCase())
      );
    }

    // Filter
    if (statusFilter === 'pending') {
      arr = arr.filter((s: any) => ['pending', 'pending_review', 'analyzed', 'uploaded', 'ai_processing'].includes(s.status));
    } else if (statusFilter === 'approved') {
      arr = arr.filter((s: any) => s.status === 'approved');
    } else if (statusFilter === 'rejected') {
      arr = arr.filter((s: any) => s.status === 'rejected');
    }
    return arr;
  }, [scans, statusFilter, activePatientFilter]);

  if (isLoading) {
    return <LoadingState message="Loading CT scan files..." />;
  }

  return (
    <View style={styles.container}>
      <View style={[styles.topAppBar, { paddingTop: Math.max(insets.top, 16) }]}>
        <View style={styles.appBarLeft}>
          <Text style={styles.appBarTitle}>Scans Directory</Text>
        </View>
        <View style={styles.appBarRight}>
          <View style={styles.avatarMini}>
            <Text style={styles.avatarMiniText}>{user?.full_name ? user.full_name.charAt(0).toUpperCase() : 'U'}</Text>
          </View>
        </View>
      </View>

      {/* Filter Bar */}
      <View style={styles.sortBar}>
        <Text style={styles.sortLabel}>Filter:</Text>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.sortOptionsRow}>
          <TouchableOpacity 
            style={[styles.sortOption, statusFilter === 'all' && styles.activeSortOption]} 
            onPress={() => setStatusFilter('all')}
            activeOpacity={0.8}
          >
            <Text style={[styles.sortOptionText, statusFilter === 'all' && styles.activeSortOptionText]}>All</Text>
          </TouchableOpacity>
          <TouchableOpacity 
            style={[styles.sortOption, statusFilter === 'pending' && styles.activeSortOption]} 
            onPress={() => setStatusFilter('pending')}
            activeOpacity={0.8}
          >
            <Text style={[styles.sortOptionText, statusFilter === 'pending' && styles.activeSortOptionText]}>Pending</Text>
          </TouchableOpacity>
          <TouchableOpacity 
            style={[styles.sortOption, statusFilter === 'approved' && styles.activeSortOption]} 
            onPress={() => setStatusFilter('approved')}
            activeOpacity={0.8}
          >
            <Text style={[styles.sortOptionText, statusFilter === 'approved' && styles.activeSortOptionText]}>Approved</Text>
          </TouchableOpacity>
          <TouchableOpacity 
            style={[styles.sortOption, statusFilter === 'rejected' && styles.activeSortOption]} 
            onPress={() => setStatusFilter('rejected')}
            activeOpacity={0.8}
          >
            <Text style={[styles.sortOptionText, statusFilter === 'rejected' && styles.activeSortOptionText]}>Rejected</Text>
          </TouchableOpacity>
        </ScrollView>
      </View>

      {activePatientFilter && (
        <View style={styles.activeFilterBanner}>
          <Text style={styles.activeFilterText}>
            Showing scans for: <Text style={{ fontWeight: 'bold' }}>{activePatientFilter}</Text>
          </Text>
          <TouchableOpacity onPress={() => setActivePatientFilter(null)}>
            <Ionicons name="close-circle" size={18} color={orvellaColors.primary} />
          </TouchableOpacity>
        </View>
      )}

      <FlatList
        data={processedScans}
        keyExtractor={(item, index) => `${item.id}-${index}`}
        refreshing={isLoading}
        onRefresh={refetch}
        ListEmptyComponent={<EmptyState title="No CT Scan files found." />}
        contentContainerStyle={styles.listContainer}
        renderItem={({ item }) => {
          const statusCfg = getStatusConfig(item.status);
          const fullImageUrl = getFullImageUrl(item.image_url);
          return (
            <Card style={styles.card} variant="elevated">
              {/* Card Header */}
              <View style={styles.cardHeader}>
                <View style={styles.scanTitleRow}>
                  <View style={[styles.statusDot, { backgroundColor: statusCfg.bg }]}>
                    <Ionicons name={statusCfg.icon as any} size={16} color={statusCfg.color} />
                  </View>
                  <View>
                    <Text style={styles.scanTitle}>Cervical Scan #{item.id}</Text>
                    <Text style={styles.scanDate}>
                      {new Date(item.created_at).toLocaleDateString('en-US', {
                        day: 'numeric', month: 'short', year: 'numeric',
                      })}
                    </Text>
                  </View>
                </View>
                <StatusBadge status={item.status} />
              </View>

              {/* Scan Image Preview */}
              <View style={styles.imageContainer}>
                {item.image_url ? (
                  <Image source={{ uri: fullImageUrl }} style={styles.scanImage} resizeMode="cover" />
                ) : (
                  <View style={styles.imagePlaceholder}>
                    <Ionicons name="scan-outline" size={36} color={orvellaColors.textMuted} />
                    <Text style={styles.imagePlaceholderText}>Image Preview Unavailable</Text>
                  </View>
                )}
              </View>

              {/* Info Rows */}
              <View style={styles.details}>
                <View style={styles.infoRow}>
                  <View style={styles.infoIconBox}>
                    <Ionicons name="person-outline" size={13} color={orvellaColors.primary} />
                  </View>
                  <Text style={styles.label}>Patient</Text>
                  <Text style={styles.value}>
                    {item.patient_name || `Patient ID: ${item.patient_id}`}
                  </Text>
                </View>

                <View style={styles.infoRow}>
                  <View style={styles.infoIconBox}>
                    <Ionicons name="medical-outline" size={13} color={orvellaColors.primary} />
                  </View>
                  <Text style={styles.label}>Specialist</Text>
                  <Text style={styles.value}>
                    {item.doctor?.full_name ? `Dr. ${item.doctor.full_name}` : 'Unassigned'}
                  </Text>
                </View>
              </View>
            </Card>
          );
        }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: orvellaColors.background,
  },
  topAppBar: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingTop: 16,
    paddingBottom: 8,
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
  listContainer: {
    padding: orvellaSpacing.md,
    paddingBottom: orvellaSpacing.xxl,
  },
  card: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    marginBottom: 12,
    padding: 12,
    borderWidth: 1,
    borderColor: '#EAECF0',
    ...orvellaShadow.sm,
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: orvellaSpacing.sm,
  },
  scanTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: orvellaSpacing.sm,
    flex: 1,
  },
  statusDot: {
    width: 34,
    height: 34,
    borderRadius: orvellaRadius.sm,
    justifyContent: 'center',
    alignItems: 'center',
  },
  scanTitle: {
    fontSize: orvellaFontSize.sm,
    fontWeight: '700',
    color: orvellaColors.textPrimary,
  },
  scanDate: {
    fontSize: 11,
    color: orvellaColors.textMuted,
    marginTop: 1,
  },
  imageContainer: {
    height: 160,
    backgroundColor: '#0c0d12',
    borderRadius: orvellaRadius.md,
    overflow: 'hidden',
    marginBottom: orvellaSpacing.sm,
  },
  scanImage: {
    width: '100%',
    height: '100%',
  },
  imagePlaceholder: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    gap: 6,
  },
  imagePlaceholderText: {
    color: orvellaColors.textMuted,
    fontSize: orvellaFontSize.xs,
    letterSpacing: 0.3,
  },
  details: {
    gap: 6,
  },
  infoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  infoIconBox: {
    width: 22,
    height: 22,
    borderRadius: 6,
    backgroundColor: orvellaColors.primaryLight,
    justifyContent: 'center',
    alignItems: 'center',
  },
  label: {
    width: 70,
    fontSize: orvellaFontSize.xs,
    color: orvellaColors.textSecondary,
    fontWeight: '600',
  },
  value: {
    flex: 1,
    fontSize: orvellaFontSize.sm,
    color: orvellaColors.textPrimary,
  },
  sortBar: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 10,
    backgroundColor: '#ffffff',
    borderBottomWidth: 1,
    borderBottomColor: '#EAECF0',
  },
  sortLabel: {
    fontSize: 12,
    fontWeight: '700',
    color: orvellaColors.textSecondary,
    marginRight: 10,
  },
  sortOptionsRow: {
    flexDirection: 'row',
    gap: 8,
    paddingRight: 16,
  },
  sortOption: {
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderRadius: 20,
    backgroundColor: '#F4F6FA',
    borderWidth: 1,
    borderColor: '#EAECF0',
  },
  activeSortOption: {
    backgroundColor: orvellaColors.primaryLight,
    borderColor: orvellaColors.primary,
  },
  sortOptionText: {
    fontSize: 11,
    fontWeight: '600',
    color: '#556987',
  },
  activeSortOptionText: {
    color: orvellaColors.primary,
    fontWeight: '700',
  },
  activeFilterBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#EEF3FF',
    paddingHorizontal: 16,
    paddingVertical: 8,
    marginHorizontal: 16,
    borderRadius: 8,
    marginBottom: 8,
  },
  activeFilterText: {
    fontSize: 13,
    color: orvellaColors.primary,
  },
});
