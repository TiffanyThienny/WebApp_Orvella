import React, { useState } from 'react';
import {
  StyleSheet,
  Text,
  View,
  TouchableOpacity,
  FlatList,
  Alert,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { useAppointments, useDoctors, useCancelAppointment } from '../../hooks/useApi';
import { orvellaColors, orvellaShadow } from '../../constants/orvella';
import { EmptyState } from '../../components/ui/StateViews';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { router } from 'expo-router';

export default function AppointmentHistoryScreen() {
  const insets = useSafeAreaInsets();
  const [filterBy, setFilterBy] = useState<'upcoming' | 'completed' | 'cancelled'>('upcoming');

  const { data: appointments, isLoading: loadingHistory, refetch: refetchHistory } = useAppointments();
  const { data: doctors } = useDoctors();
  const cancelAppointment = useCancelAppointment();

  const getFilteredAppointments = () => {
    if (!appointments) return [];
    
    if (filterBy === 'upcoming') {
      const upcoming = appointments.filter((a: any) => a.status === 'pending' || a.status === 'approved' || a.status === 'confirmed');
      // Sort closest first (ascending date)
      return [...upcoming].sort(
        (a, b) => new Date(a.appointment_date).getTime() - new Date(b.appointment_date).getTime()
      );
    }
    
    // Sort completed and cancelled newest first (descending date)
    const sorted = [...appointments].sort(
      (a, b) => new Date(b.appointment_date).getTime() - new Date(a.appointment_date).getTime()
    );
    
    if (filterBy === 'completed') {
      return sorted.filter((a: any) => a.status === 'completed' || a.status === 'done');
    }
    if (filterBy === 'cancelled') {
      return sorted.filter((a: any) => a.status === 'cancelled' || a.status === 'rejected');
    }
    return sorted;
  };

  const handleCancel = (appointmentId: number) => {
    Alert.alert(
      'Cancel Appointment',
      'Are you sure you want to cancel this scheduled consultation?',
      [
        { text: 'No', style: 'cancel' },
        {
          text: 'Yes, Cancel',
          style: 'destructive',
          onPress: async () => {
            try {
              await cancelAppointment.mutateAsync(appointmentId);
              Alert.alert('Success', 'Appointment cancelled successfully.');
              refetchHistory();
            } catch (err: any) {
              console.error(err);
              Alert.alert('Failed', err.response?.data?.error || err.message || 'Failed to cancel appointment.');
            }
          },
        },
      ]
    );
  };

  return (
    <View style={styles.container}>
      <View style={[styles.header, { paddingTop: Math.max(insets.top, 16) }]}>
        <TouchableOpacity 
          style={styles.backButton} 
          onPress={() => router.push('/(patient)/dashboard')}
          activeOpacity={0.7}
        >
          <Ionicons name="arrow-back" size={20} color="#1E293B" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Appointment</Text>
        <View style={{ width: 40 }} />
      </View>

      <View style={styles.tabBar}>
        {[
          { key: 'upcoming', label: 'Upcoming' },
          { key: 'completed', label: 'Completed' },
          { key: 'cancelled', label: 'Cancelled' },
        ].map((tab) => {
          const isActive = filterBy === tab.key;
          return (
            <TouchableOpacity
              key={tab.key}
              style={[styles.tabItem, isActive && styles.tabItemActive]}
              onPress={() => setFilterBy(tab.key as any)}
              activeOpacity={0.8}
            >
              <Text style={[styles.tabText, isActive && styles.tabTextActive]}>
                {tab.label}
              </Text>
            </TouchableOpacity>
          );
        })}
      </View>

      <FlatList
        data={getFilteredAppointments()}
        keyExtractor={(item) => item.id.toString()}
        refreshing={loadingHistory}
        onRefresh={refetchHistory}
        contentContainerStyle={styles.listContainer}
        ListEmptyComponent={
          <EmptyState
            title="No Appointments Found"
            subtitle="You do not have any appointments matching this filter."
          />
        }
        renderItem={({ item }) => {
          // Cancel only allowed up to H-2 (cannot cancel on H-1 or Day H, and not allowed if already approved)
          const isCancelable = (() => {
            if (item.status !== 'pending' && item.status !== 'confirmed') return false;
            const apptDate = new Date(item.appointment_date);
            const apptMidnight = new Date(apptDate.getFullYear(), apptDate.getMonth(), apptDate.getDate());
            const today = new Date();
            const todayMidnight = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            const diffTime = apptMidnight.getTime() - todayMidnight.getTime();
            const diffDays = diffTime / (1000 * 60 * 60 * 24);
            return diffDays >= 2;
          })();
          
          return (
            <View style={styles.appointmentCard}>
              <View style={styles.doctorRow}>
                <View style={styles.doctorAvatar}>
                  <Text style={styles.avatarText}>
                    {item.doctor_name ? item.doctor_name.charAt(0).toUpperCase() : 'D'}
                  </Text>
                </View>
                <View style={styles.doctorMeta}>
                  <Text style={styles.doctorName}>Dr. {item.doctor_name || 'Specialist'}</Text>
                  <Text style={styles.doctorSpecialty}>
                    {doctors?.find((d: any) => d.id === item.doctor_id)?.specialty || 'Pulmonologist & Respiratory Specialist'}
                  </Text>
                </View>
                <StatusBadge status={item.status} size="sm" />
              </View>

              <View style={styles.cardDivider} />

              <View style={styles.dateTimeRow}>
                <View style={styles.dateTimeBlock}>
                  <View style={styles.iconContainer}>
                    <Ionicons name="calendar-outline" size={16} color={orvellaColors.primary} />
                  </View>
                  <View>
                    <Text style={styles.dateTimeLabel}>Date</Text>
                    <Text style={styles.dateTimeValue}>
                      {(() => {
                        const rawDate = item.appointment_date ? item.appointment_date.split('T')[0] : '';
                        const d = rawDate ? new Date(`${rawDate}T12:00:00Z`) : new Date();
                        return d.toLocaleDateString('en-US', { day: 'numeric', month: 'long', year: 'numeric' });
                      })()}
                    </Text>
                  </View>
                </View>

                <View style={styles.dateTimeBlock}>
                  <View style={styles.iconContainer}>
                    <Ionicons name="time-outline" size={16} color={orvellaColors.primary} />
                  </View>
                  <View>
                    <Text style={styles.dateTimeLabel}>Time</Text>
                    <Text style={styles.dateTimeValue}>
                      {(() => {
                        let startStr = '00:00';
                        if (item.appointment_date) {
                          const timeMatch = item.appointment_date.match(/T(\d{2}:\d{2})/);
                          if (timeMatch) startStr = timeMatch[1];
                        }
                        const [hours, minutes] = startStr.split(':').map(Number);
                        const ampm = hours >= 12 ? 'PM' : 'AM';
                        const formattedHours = hours % 12 || 12;
                        return `${formattedHours}:${minutes.toString().padStart(2, '0')} ${ampm}`;
                      })()}
                    </Text>
                  </View>
                </View>
              </View>

              {item.notes ? (
                <View style={styles.notesContainer}>
                  <Text style={styles.notesText}>Notes: "{item.notes}"</Text>
                </View>
              ) : null}

              {isCancelable && (
                <View style={styles.actionRow}>
                  <TouchableOpacity
                    style={[styles.btnSecondary, { width: '100%' }]}
                    onPress={() => handleCancel(item.id)}
                    activeOpacity={0.7}
                  >
                    <Text style={styles.btnSecondaryText}>Cancel Appointment</Text>
                  </TouchableOpacity>
                </View>
              )}

              {(item.status === 'completed' || item.status === 'cancelled') && (
                <View style={styles.actionRow}>
                  <TouchableOpacity
                    style={[styles.btnPrimary, { width: '100%' }]}
                    onPress={() => router.push('/(patient)/appointments')}
                    activeOpacity={0.7}
                  >
                    <Text style={styles.btnPrimaryText}>Book Appointment Again</Text>
                  </TouchableOpacity>
                </View>
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
    backgroundColor: '#F8FAFC',
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
    paddingBottom: 16,
    backgroundColor: '#ffffff',
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
  tabBar: {
    flexDirection: 'row',
    backgroundColor: '#ffffff',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
  },
  tabItem: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: 14,
    borderBottomWidth: 2,
    borderBottomColor: 'transparent',
  },
  tabItemActive: {
    borderBottomColor: orvellaColors.primary,
  },
  tabText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#94A3B8',
  },
  tabTextActive: {
    color: orvellaColors.primary,
    fontWeight: '700',
  },
  listContainer: {
    padding: 16,
    paddingBottom: 40,
  },
  appointmentCard: {
    backgroundColor: '#ffffff',
    borderRadius: 20,
    padding: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    ...orvellaShadow.sm,
  },
  doctorRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  doctorAvatar: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#EEF3FF',
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#BFDBFE',
  },
  avatarText: {
    fontSize: 18,
    fontWeight: '800',
    color: orvellaColors.primary,
  },
  doctorMeta: {
    flex: 1,
  },
  doctorName: {
    fontSize: 15,
    fontWeight: '700',
    color: '#1E293B',
  },
  doctorSpecialty: {
    fontSize: 11,
    color: '#64748B',
    marginTop: 2,
  },
  videoBadge: {
    width: 36,
    height: 36,
    borderRadius: 10,
    backgroundColor: '#EFF6FF',
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#DBEAFE',
  },
  cardDivider: {
    height: 1,
    backgroundColor: '#F1F5F9',
    marginVertical: 16,
  },
  dateTimeRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 12,
  },
  dateTimeBlock: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  iconContainer: {
    width: 34,
    height: 34,
    borderRadius: 10,
    backgroundColor: '#EFF6FF',
    justifyContent: 'center',
    alignItems: 'center',
  },
  dateTimeLabel: {
    fontSize: 11,
    color: '#94A3B8',
    fontWeight: '500',
  },
  dateTimeValue: {
    fontSize: 12,
    fontWeight: '700',
    color: '#1E293B',
    marginTop: 1,
  },
  notesContainer: {
    marginTop: 14,
    backgroundColor: '#F8FAFC',
    borderRadius: 10,
    padding: 10,
    borderWidth: 1,
    borderColor: '#F1F5F9',
  },
  notesText: {
    fontSize: 11,
    color: '#64748B',
    fontStyle: 'italic',
    lineHeight: 16,
  },
  actionRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 12,
    marginTop: 16,
  },
  btnPrimary: {
    flex: 1,
    backgroundColor: orvellaColors.primary,
    paddingVertical: 10,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnPrimaryText: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '700',
  },
  btnSecondary: {
    flex: 1,
    backgroundColor: '#F1F5F9',
    paddingVertical: 10,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnSecondaryText: {
    color: '#64748B',
    fontSize: 13,
    fontWeight: '700',
  },
});

