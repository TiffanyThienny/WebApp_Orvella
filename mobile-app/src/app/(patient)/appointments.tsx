import React, { useState, useEffect } from 'react';
import {
  StyleSheet,
  Text,
  View,
  ScrollView,
  TouchableOpacity,
  TextInput,
  Alert,
  FlatList,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import {
  useAppointments,
  useDoctors,
  useDoctorSchedules,
  useBookAppointment,
  useCancelAppointment,
} from '../../hooks/useApi';
import {
  orvellaColors,
  orvellaFontSize,
  orvellaSpacing,
  orvellaRadius,
  orvellaShadow,
} from '../../constants/orvella';
import { Card } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { EmptyState } from '../../components/ui/StateViews';
import { useLocalSearchParams, router } from 'expo-router';



// English day of week helper
const englishDays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const monthNames = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December'
];

const daysOfWeekHeaders = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

export default function AppointmentsScreen() {
  const params = useLocalSearchParams();
  const insets = useSafeAreaInsets();

  // Filter state for appointment history
  const [filterBy, setFilterBy] = useState<'all' | 'pending' | 'active' | 'completed' | 'cancelled'>('all');

  // Booking states
  const [selectedDoctorId, setSelectedDoctorId] = useState<number | null>(null);
  const [isDoctorListExpanded, setIsDoctorListExpanded] = useState(true);
  const [selectedDateStr, setSelectedDateStr] = useState<string | null>(null); // YYYY-MM-DD
  const [selectedSchedule, setSelectedSchedule] = useState<any | null>(null);
  const [notes, setNotes] = useState('');

  // Calendar states
  const [currentYear, setCurrentYear] = useState(new Date().getFullYear());
  const [currentMonth, setCurrentMonth] = useState(new Date().getMonth()); // 0-indexed

  // Queries
  const { data: appointments, isLoading: loadingHistory, refetch: refetchHistory } = useAppointments();
  const { data: doctors, isLoading: loadingDoctors } = useDoctors();
  const { data: schedules, isLoading: loadingSchedules } = useDoctorSchedules(selectedDoctorId);

  // Mutations
  const bookAppointment = useBookAppointment();
  const cancelAppointment = useCancelAppointment();

  const getFilteredAppointments = () => {
    if (!appointments) return [];
    const sorted = [...appointments].sort(
      (a, b) => new Date(b.appointment_date).getTime() - new Date(a.appointment_date).getTime()
    );
    if (filterBy === 'all') return sorted;
    if (filterBy === 'pending') return sorted.filter((a: any) => a.status === 'pending');
    if (filterBy === 'active') return sorted.filter((a: any) => a.status === 'approved' || a.status === 'confirmed');
    if (filterBy === 'completed') return sorted.filter((a: any) => a.status === 'completed' || a.status === 'done');
    if (filterBy === 'cancelled') return sorted.filter((a: any) => a.status === 'cancelled' || a.status === 'rejected');
    return sorted;
  };



  const handlePrevMonth = () => {
    if (currentMonth === 0) {
      setCurrentMonth(11);
      setCurrentYear((y) => y - 1);
    } else {
      setCurrentMonth((m) => m - 1);
    }
  };

  const handleNextMonth = () => {
    if (currentMonth === 11) {
      setCurrentMonth(0);
      setCurrentYear((y) => y + 1);
    } else {
      setCurrentMonth((m) => m + 1);
    }
  };

  const getDateString = (day: number) => {
    const year = currentYear;
    const month = String(currentMonth + 1).padStart(2, '0');
    const dayStr = String(day).padStart(2, '0');
    return `${year}-${month}-${dayStr}`;
  };

  const isDatePast = (day: number) => {
    const dateStr = getDateString(day);
    const todayStr = new Date().toISOString().split('T')[0];
    return dateStr < todayStr;
  };

  const getDoctorScheduleForDate = (dateStr: string) => {
    if (!schedules || schedules.length === 0) return null;
    const [year, month, day] = dateStr.split('-').map(Number);
    const dateObj = new Date(year, month - 1, day);
    const dayName = englishDays[dateObj.getDay()];

    return schedules.find((s: any) => {
      if (!s.is_available) return false;

      if (s.appointment_date) {
        const start = s.appointment_date.substring(0, 10);
        const end = s.end_date ? s.end_date.substring(0, 10) : start;
        return dateStr >= start && dateStr <= end;
      }

      return s.day_of_week === dayName;
    });
  };

  const isDateUnavailable = (day: number) => {
    if (isDatePast(day)) return true;
    if (!selectedDoctorId) return false;
    const dateStr = getDateString(day);
    return !getDoctorScheduleForDate(dateStr);
  };

  const getSlotsRemaining = (day: number) => {
    if (!selectedDoctorId) return 0;
    const dateStr = getDateString(day);
    const schedule = getDoctorScheduleForDate(dateStr);
    if (!schedule) return 0;

    const maxPatients = schedule.max_patients ?? 5;
    const bookedCount = schedule.booked_count ?? 0;

    // Count patient's own bookings for that day (except rejected/cancelled)
    const myBookings = (appointments || []).filter((a: any) => 
      a.doctor_id === selectedDoctorId &&
      a.appointment_date.startsWith(dateStr) &&
      a.status !== 'rejected' &&
      a.status !== 'cancelled'
    ).length;

    const booked = Math.max(bookedCount, myBookings);
    return Math.max(0, maxPatients - booked);
  };

  const hasOwnAppointment = (day: number) => {
    const target = getDateString(day);
    return (appointments || []).some((a: any) =>
      a.appointment_date.startsWith(target) &&
      (!selectedDoctorId || String(a.doctor_id) === String(selectedDoctorId)) &&
      a.status !== 'rejected' &&
      a.status !== 'cancelled'
    );
  };

  const handleDoctorSelect = (doctorId: number) => {
    setSelectedDoctorId(doctorId);
    setSelectedDateStr(null);
    setSelectedSchedule(null);
  };

  const handleDateSelect = (day: number) => {
    const dateStr = getDateString(day);
    const schedule = getDoctorScheduleForDate(dateStr);
    if (!schedule) return;

    if (getSlotsRemaining(day) === 0) {
      Alert.alert('Quota Full', 'The doctor\'s consultation quota for this date is full.');
      return;
    }

    setSelectedDateStr(dateStr);
    setSelectedSchedule(schedule);
  };

  const handleBook = async () => {
    if (!selectedDoctorId) {
      Alert.alert('Validation', 'Please select a specialist doctor first.');
      return;
    }
    if (!selectedDateStr || !selectedSchedule) {
      Alert.alert('Validation', 'Please select a date and consultation session.');
      return;
    }
    if (!notes.trim()) {
      Alert.alert('Validation', 'Please enter your primary symptoms/complaints.');
      return;
    }

    try {
      let startTime = selectedSchedule.start_time;
      if (startTime.length === 5) {
        startTime = `${startTime}:00`;
      }
      const appointmentDateISO = `${selectedDateStr}T${startTime}Z`;

      await bookAppointment.mutateAsync({
        doctor_id: selectedDoctorId,
        appointment_date: appointmentDateISO,
        notes: notes.trim(),
      });

      Alert.alert('Success', 'Your appointment has been successfully scheduled!', [
        {
          text: 'View History',
          onPress: () => {
            setSelectedDateStr(null);
            setSelectedSchedule(null);
            setNotes('');
            router.push('/(patient)/history');
          },
        },
      ]);
    } catch (err: any) {
      console.error(err);
      Alert.alert('Scheduling Failed', err.response?.data?.error || err.message || 'System error occurred.');
    }
  };

  const handleCancel = (appointmentId: number) => {
    Alert.alert(
      'Cancel Appointment',
      'Are you sure you want to cancel this consultation scheduled?',
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

  const selectedDoctor = doctors?.find((d: any) => d.id === selectedDoctorId);

  // Month grid variables
  const totalDays = new Date(currentYear, currentMonth + 1, 0).getDate();
  const blankDaysCount = new Date(currentYear, currentMonth, 1).getDay();
  const blankDays = Array.from({ length: blankDaysCount }, (_, i) => i);
  const monthDays = Array.from({ length: totalDays }, (_, i) => i + 1);

  const isDoctorAlreadyBooked = (doctorId: number) => {
    return (appointments || []).some((a: any) =>
      Number(a.doctor_id) === Number(doctorId) &&
      (a.status === 'pending' || a.status === 'approved' || a.status === 'confirmed')
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
        <Text style={styles.headerTitle}>Book Appointment</Text>
        <View style={{ width: 40 }} />
      </View>

      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        keyboardVerticalOffset={Platform.OS === 'ios' ? 100 : 0}
      >
        <ScrollView style={styles.scrollContainer} contentContainerStyle={styles.scrollContent} keyboardShouldPersistTaps="handled">
          
          {/* Step 1: Doctor Selection */}
          <Text style={styles.formLabel}>Select Specialist Doctor</Text>
          <View style={{ marginBottom: 12 }}>
            {loadingDoctors ? (
              <ActivityIndicator size="small" color={orvellaColors.primary} style={{ margin: 20 }} />
            ) : !doctors || doctors.length === 0 ? (
              <Text style={styles.placeholderText}>No doctors available at this time.</Text>
            ) : !isDoctorListExpanded && selectedDoctor ? (
              <View style={[styles.doctorCard, styles.doctorCardSelected]}>
                <View style={[styles.doctorAvatar, styles.doctorAvatarSelected]}>
                  <Text style={[styles.avatarInitialText, { color: '#ffffff' }]}>
                    {selectedDoctor.full_name ? selectedDoctor.full_name.charAt(0).toUpperCase() : 'D'}
                  </Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.doctorCardName}>
                    Dr. {selectedDoctor.full_name}
                  </Text>
                  <Text style={styles.doctorCardSpecialty}>
                    {selectedDoctor.specialty || 'Pulmonologist & Respiratory Specialist'}
                  </Text>
                </View>
                <TouchableOpacity 
                  style={styles.changeDoctorBtn} 
                  onPress={() => {
                    setIsDoctorListExpanded(true);
                    setSelectedDateStr(null);
                    setSelectedSchedule(null);
                  }}
                  activeOpacity={0.7}
                >
                  <Text style={styles.changeDoctorBtnText}>Change</Text>
                </TouchableOpacity>
              </View>
            ) : (
              <View style={styles.doctorsGrid}>
                {doctors.map((d: any) => {
                  const isSelected = d.id === selectedDoctorId;
                  const isBooked = isDoctorAlreadyBooked(d.id);
                  return (
                    <TouchableOpacity
                      key={d.id}
                      style={[
                        styles.doctorCard,
                        isSelected && styles.doctorCardSelected,
                      ]}
                      onPress={() => {
                        handleDoctorSelect(d.id);
                        setIsDoctorListExpanded(false);
                      }}
                      activeOpacity={0.85}
                    >
                      <View style={[
                        styles.doctorAvatar,
                        isSelected && styles.doctorAvatarSelected,
                      ]}>
                        <Text style={[styles.avatarInitialText, isSelected && { color: '#ffffff' }]}>
                          {d.full_name ? d.full_name.charAt(0).toUpperCase() : 'D'}
                        </Text>
                      </View>
                      <View style={{ flex: 1 }}>
                        <Text style={styles.doctorCardName}>
                          Dr. {d.full_name}
                        </Text>
                        <Text style={styles.doctorCardSpecialty}>
                          {d.specialty || 'Pulmonologist & Respiratory Specialist'}
                        </Text>
                        {isBooked && (
                          <View style={styles.bookedPill}>
                            <Ionicons name="time-outline" size={11} color={orvellaColors.primary} />
                            <Text style={styles.bookedPillText}>Active Appointment Scheduled</Text>
                          </View>
                        )}
                      </View>
                      <View style={[styles.selectCircle, isSelected && styles.selectCircleActive]}>
                        {isSelected && <Ionicons name="checkmark" size={12} color="#ffffff" />}
                      </View>
                    </TouchableOpacity>
                  );
                })}
              </View>
            )}
          </View>

          {/* Step 2: Interactive Monthly Calendar */}
          {selectedDoctorId && (
            <>
              <Text style={styles.formLabel}>Select Consultation Date</Text>
              <View style={styles.inputContainer}>
                {loadingSchedules ? (
                  <ActivityIndicator size="small" color={orvellaColors.primary} style={{ margin: 20 }} />
                ) : !schedules || schedules.length === 0 ? (
                  <View style={styles.noScheduleContainer}>
                    <Ionicons name="alert-circle-outline" size={32} color={orvellaColors.textMuted} />
                    <Text style={styles.noScheduleText}>
                      This specialist has not configured a schedule yet.
                    </Text>
                  </View>
                ) : (
                  <View style={styles.calendarContainer}>
                    {/* Calendar Navigation */}
                    <View style={styles.calendarNav}>
                      <TouchableOpacity onPress={handlePrevMonth} style={styles.navBtn}>
                        <Ionicons name="chevron-back" size={18} color={orvellaColors.textPrimary} />
                      </TouchableOpacity>
                      <Text style={styles.calendarMonthTitle}>
                        {monthNames[currentMonth]} {currentYear}
                      </Text>
                      <TouchableOpacity onPress={handleNextMonth} style={styles.navBtn}>
                        <Ionicons name="chevron-forward" size={18} color={orvellaColors.textPrimary} />
                      </TouchableOpacity>
                    </View>

                    {/* Days of Week Headers */}
                    <View style={styles.daysHeaderRow}>
                      {daysOfWeekHeaders.map((dayName, idx) => (
                        <Text key={idx} style={styles.dayHeaderCell}>
                          {dayName}
                        </Text>
                      ))}
                    </View>

                    {/* Calendar Days Grid */}
                    <View style={styles.daysGrid}>
                      {blankDays.map((_, idx) => (
                        <View key={`blank-${idx}`} style={styles.blankDayCell} />
                      ))}
                      {monthDays.map((day) => {
                        const dateStr = getDateString(day);
                        const isUnavailable = isDateUnavailable(day);
                        const isSelected = selectedDateStr === dateStr;
                        const hasOwn = hasOwnAppointment(day);
                        const slotsRemaining = getSlotsRemaining(day);
                        const schedule = getDoctorScheduleForDate(dateStr);

                        let dotColor = '#10B981'; // Green (Quota Available)
                        if (slotsRemaining === 0) {
                          dotColor = '#EF4444'; // Red (Full)
                        } else if (slotsRemaining <= 2) {
                          dotColor = '#F59E0B'; // Orange (Filling up)
                        }

                        return (
                          <TouchableOpacity
                            key={`day-${day}`}
                            disabled={isUnavailable}
                            style={[
                              styles.dayCell,
                              isSelected && styles.dayCellSelected,
                              isUnavailable && styles.dayCellDisabled,
                            ]}
                            onPress={() => handleDateSelect(day)}
                          >
                            <Text
                              style={[
                                styles.dayNumber,
                                isUnavailable && styles.dayNumberDisabled,
                                isSelected && styles.dayNumberSelected,
                              ]}
                            >
                              {day}
                            </Text>
                            
                            {!isUnavailable && schedule && (
                              <View style={[
                                styles.indicatorDot,
                                isSelected && { backgroundColor: '#ffffff' },
                                !isSelected && { backgroundColor: dotColor }
                              ]} />
                            )}
                            
                            {hasOwn && !isSelected && (
                              <View style={styles.ownAppointmentDot} />
                            )}
                          </TouchableOpacity>
                        );
                      })}
                    </View>

                    {/* Calendar Legend */}
                    <View style={styles.legendContainer}>
                      <View style={styles.legendItem}>
                        <View style={[styles.legendDot, { backgroundColor: '#10B981' }]} />
                        <Text style={styles.legendText}>Available</Text>
                      </View>
                      <View style={styles.legendItem}>
                        <View style={[styles.legendDot, { backgroundColor: '#F59E0B' }]} />
                        <Text style={styles.legendText}>Filling Up</Text>
                      </View>
                      <View style={styles.legendItem}>
                        <View style={[styles.legendDot, { backgroundColor: '#EF4444' }]} />
                        <Text style={styles.legendText}>Full</Text>
                      </View>
                    </View>
                  </View>
                )}
              </View>
            </>
          )}

          {/* Step 3: Available Session Selector */}
          {selectedDateStr && selectedSchedule && (
            <>
              <Text style={styles.formLabel}>Available Session</Text>
              <View style={styles.sessionGrid}>
                <View style={styles.sessionCardActive}>
                  <Ionicons name="time" size={16} color="#ffffff" style={{ marginRight: 6 }} />
                  <Text style={styles.sessionTimeTextActive}>
                    {selectedSchedule.start_time.substring(0, 5)} - {selectedSchedule.end_time.substring(0, 5)}
                  </Text>
                </View>
                <Text style={styles.quotaText}>
                  {Math.max(0, (selectedSchedule.max_patients ?? 5) - (selectedSchedule.booked_count ?? 0))} consultation slots remaining
                </Text>
              </View>
            </>
          )}

          {/* Step 4: Keluhan Input & Confirm */}
          {selectedDoctorId && selectedDateStr && selectedSchedule && (
            <>
              <Text style={styles.formLabel}>Write your problem</Text>
              <View style={styles.notesInputCard}>
                <TextInput
                  style={styles.textArea}
                  placeholder="Briefly describe your symptoms, medical concerns, or post-operative follow-up needs..."
                  multiline
                  numberOfLines={4}
                  value={notes}
                  onChangeText={setNotes}
                  placeholderTextColor="#94A3B8"
                />
              </View>

              <TouchableOpacity
                style={styles.btnPrimary}
                onPress={handleBook}
                disabled={bookAppointment.isPending}
                activeOpacity={0.8}
              >
                <Text style={styles.btnPrimaryText}>
                  {bookAppointment.isPending ? 'Registering...' : 'Continue >'}
                </Text>
              </TouchableOpacity>
            </>
          )}
        </ScrollView>
      </KeyboardAvoidingView>
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
  scrollContainer: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: 20,
    paddingTop: 16,
    paddingBottom: 200,
  },
  formLabel: {
    fontSize: 14,
    fontWeight: '700',
    color: '#0F172A',
    marginBottom: 8,
    marginTop: 12,
  },
  inputContainer: {
    backgroundColor: '#ffffff',
    borderRadius: 20,
    padding: 16,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    marginBottom: 16,
    ...orvellaShadow.sm,
  },
  notesInputCard: {
    backgroundColor: '#ffffff',
    borderRadius: 20,
    padding: 12,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    marginBottom: 20,
    minHeight: 120,
    ...orvellaShadow.sm,
  },
  textArea: {
    fontSize: 14,
    color: '#1E293B',
    textAlignVertical: 'top',
    height: 100,
    paddingVertical: orvellaSpacing.sm,
  },
  sessionGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    alignItems: 'center',
    gap: 12,
    marginBottom: 16,
  },
  sessionCardActive: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: orvellaColors.primary,
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 12,
  },
  sessionTimeTextActive: {
    color: '#ffffff',
    fontSize: 13,
    fontWeight: '700',
  },
  quotaText: {
    fontSize: 12,
    color: '#64748B',
    fontWeight: '500',
  },
  btnPrimary: {
    backgroundColor: orvellaColors.primary,
    paddingVertical: 14,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 8,
    ...orvellaShadow.sm,
  },
  btnPrimaryText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '700',
  },
  doctorsGrid: {
    gap: 12,
  },
  calendarDocLabel: {
    fontSize: 12,
    fontWeight: 'bold',
    color: orvellaColors.primary,
  },
  placeholderText: {
    textAlign: 'center',
    color: orvellaColors.textMuted,
    fontSize: orvellaFontSize.sm,
    marginVertical: orvellaSpacing.md,
  },
  doctorCard: {
    width: '100%',
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderRadius: 14,
    padding: 14,
    borderWidth: 1.5,
    borderColor: '#EAECF0',
    ...orvellaShadow.sm,
    gap: 14,
  },
  doctorCardSelected: {
    borderColor: orvellaColors.primary,
    backgroundColor: orvellaColors.primaryLight,
  },
  doctorAvatar: {
    width: 44,
    height: 44,
    borderRadius: 12,
    backgroundColor: '#EEF3FF',
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#BFDBFE',
  },
  doctorAvatarSelected: {
    backgroundColor: orvellaColors.primary,
    borderColor: orvellaColors.primary,
  },
  doctorCardName: {
    fontSize: 14,
    fontWeight: 'bold',
    color: orvellaColors.textPrimary,
  },
  doctorCardSpecialty: {
    fontSize: 11,
    color: orvellaColors.textSecondary,
    marginTop: 2,
  },
  bookedPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#EFF6FF',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
    alignSelf: 'flex-start',
    marginTop: 6,
    borderWidth: 1,
    borderColor: '#BFDBFE',
  },
  bookedPillText: {
    fontSize: 9,
    fontWeight: 'bold',
    color: orvellaColors.primary,
  },
  selectCircle: {
    width: 20,
    height: 20,
    borderRadius: 10,
    borderWidth: 1.5,
    borderColor: '#D1D5DB',
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: '#ffffff',
  },
  selectCircleActive: {
    borderColor: orvellaColors.primary,
    backgroundColor: orvellaColors.primary,
  },
  sortContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: orvellaSpacing.md,
    paddingVertical: 10,
    backgroundColor: '#ffffff',
    borderBottomWidth: 1,
    borderBottomColor: orvellaColors.border,
  },
  modalTitle: {
    fontSize: 22,
    fontWeight: '800',
    color: orvellaColors.textPrimary,
    marginRight: 10,
  },
  sortLabel: {
    fontSize: 12,
    fontWeight: 'bold',
    color: orvellaColors.textPrimary,
    marginRight: 10,
  },
  sortScroll: {
    gap: 8,
  },
  sortPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 20,
    backgroundColor: '#F3F4F6',
    borderWidth: 1,
    borderColor: '#E5E7EB',
  },
  sortPillActive: {
    backgroundColor: orvellaColors.primary,
    borderColor: orvellaColors.primary,
  },
  sortPillText: {
    fontSize: 11,
    fontWeight: '600',
    color: orvellaColors.textSecondary,
  },
  sortPillTextActive: {
    color: '#ffffff',
    fontWeight: 'bold',
  },
  noScheduleContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: orvellaSpacing.md,
    gap: 8,
  },
  noScheduleText: {
    fontSize: orvellaFontSize.sm,
    color: orvellaColors.textSecondary,
    textAlign: 'center',
    lineHeight: 20,
    paddingHorizontal: orvellaSpacing.sm,
  },
  inputLabel: {
    fontSize: orvellaFontSize.sm,
    fontWeight: 'bold',
    color: orvellaColors.textSecondary,
    marginBottom: orvellaSpacing.sm,
  },
  calendarContainer: {
    backgroundColor: orvellaColors.surface,
    borderRadius: orvellaRadius.md,
  },
  calendarNav: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: orvellaColors.surfaceVariant,
    paddingVertical: 10,
    paddingHorizontal: orvellaSpacing.md,
    borderRadius: orvellaRadius.md,
    marginBottom: orvellaSpacing.md,
  },
  navBtn: {
    padding: 6,
    borderRadius: orvellaRadius.sm,
    backgroundColor: orvellaColors.surface,
    ...orvellaShadow.sm,
  },
  calendarMonthTitle: {
    fontSize: orvellaFontSize.md,
    fontWeight: 'bold',
    color: orvellaColors.textPrimary,
  },
  daysHeaderRow: {
    flexDirection: 'row',
    marginBottom: 8,
  },
  dayHeaderCell: {
    flex: 1,
    textAlign: 'center',
    fontSize: 11,
    fontWeight: 'bold',
    color: orvellaColors.textMuted,
  },
  daysGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    rowGap: 8,
  },
  blankDayCell: {
    width: '14.28%',
    aspectRatio: 1,
  },
  dayCell: {
    width: '14.28%',
    aspectRatio: 1,
    justifyContent: 'center',
    alignItems: 'center',
    borderRadius: 22,
    marginVertical: 2,
    backgroundColor: '#ffffff',
  },
  dayCellSelected: {
    backgroundColor: orvellaColors.primary,
  },
  dayCellDisabled: {
    opacity: 0.15,
  },
  dayNumber: {
    fontSize: 13,
    fontWeight: '700',
    color: orvellaColors.textPrimary,
  },
  dayNumberSelected: {
    color: '#ffffff',
  },
  dayNumberDisabled: {
    color: orvellaColors.textMuted,
  },
  avatarInitialText: {
    fontSize: 16,
    fontWeight: '800',
    color: orvellaColors.primary,
  },
  indicatorDot: {
    width: 4,
    height: 4,
    borderRadius: 2,
    marginTop: 3,
  },
  ownAppointmentDot: {
    position: 'absolute',
    top: 4,
    right: 4,
    width: 4,
    height: 4,
    borderRadius: 2,
    backgroundColor: orvellaColors.primary,
  },
  slotsBadge: {
    paddingHorizontal: 4,
    paddingVertical: 1,
    borderRadius: 3,
    minWidth: 26,
    alignItems: 'center',
  },
  slotsBadgeText: {
    fontSize: 7,
    fontWeight: '800',
  },
  legendContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 16,
    marginTop: orvellaSpacing.md,
    paddingTop: orvellaSpacing.sm,
    borderTopWidth: 1,
    borderTopColor: orvellaColors.border,
  },
  legendItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  legendDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
  },
  legendText: {
    fontSize: 10,
    color: orvellaColors.textSecondary,
    fontWeight: '500',
  },
  selectedSlotCard: {
    backgroundColor: orvellaColors.accentLight,
    borderWidth: 1,
    borderColor: orvellaColors.accent,
    borderRadius: orvellaRadius.md,
    padding: orvellaSpacing.md,
    marginTop: orvellaSpacing.md,
  },
  slotHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 4,
  },
  slotTimeRange: {
    fontSize: orvellaFontSize.lg,
    fontWeight: 'bold',
    color: orvellaColors.textPrimary,
  },
  slotLimitText: {
    fontSize: orvellaFontSize.xs,
    color: orvellaColors.textSecondary,
  },
  input: {
    backgroundColor: orvellaColors.surfaceVariant,
    borderRadius: orvellaRadius.md,
    borderWidth: 1,
    borderColor: orvellaColors.border,
    paddingHorizontal: orvellaSpacing.md,
    height: 48,
    fontSize: orvellaFontSize.sm,
    color: orvellaColors.textPrimary,
    marginBottom: orvellaSpacing.md,
  },
  bookButton: {
    marginTop: orvellaSpacing.sm,
  },
  listContainer: {
    padding: orvellaSpacing.md,
    gap: orvellaSpacing.md,
    paddingBottom: orvellaSpacing.xxl,
  },
  historyCard: {
    padding: orvellaSpacing.md,
    ...orvellaShadow.sm,
  },
  historyHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    borderBottomWidth: 1,
    borderBottomColor: orvellaColors.border,
    paddingBottom: orvellaSpacing.sm,
    marginBottom: orvellaSpacing.sm,
  },
  doctorInfoCol: {
    flex: 1,
  },
  historyDoctorName: {
    fontSize: orvellaFontSize.md,
    fontWeight: 'bold',
    color: orvellaColors.textPrimary,
  },
  historySpecialty: {
    fontSize: orvellaFontSize.xs,
    color: orvellaColors.textSecondary,
    marginTop: 2,
  },
  historyDetails: {
    gap: orvellaSpacing.xs,
  },
  detailRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  detailText: {
    fontSize: orvellaFontSize.sm,
    color: orvellaColors.textPrimary,
  },
  detailTextNotes: {
    fontSize: orvellaFontSize.sm,
    color: orvellaColors.textSecondary,
    flex: 1,
    lineHeight: 18,
    fontStyle: 'italic',
  },
  historyActionRow: {
    marginTop: orvellaSpacing.md,
    borderTopWidth: 1,
    borderTopColor: orvellaColors.border,
    paddingTop: orvellaSpacing.sm,
    alignItems: 'flex-end',
  },
  cancelBtn: {
    borderColor: orvellaColors.danger,
    minHeight: 36,
  },
  changeDoctorBtn: {
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 10,
    backgroundColor: '#F1F5F9',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  changeDoctorBtnText: {
    fontSize: 12,
    color: orvellaColors.primary,
    fontWeight: '700',
  },
});
