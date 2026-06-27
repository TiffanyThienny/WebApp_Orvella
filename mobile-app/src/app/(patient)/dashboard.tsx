import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  TouchableOpacity,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import Svg, { Path, Circle, Text as SvgText, Line, G, LinearGradient, Stop, Rect, Defs } from 'react-native-svg';
import { useAuthStore } from '../../store/auth.store';
import { useAppointments, useScans, useHealthRecords, useDoctors } from '../../hooks/useApi';
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


export default function PatientDashboard() {
  const { user, logout } = useAuthStore();
  const insets = useSafeAreaInsets();
  const { data: appointments, isLoading: loadingAppts, refetch: refetchAppts } = useAppointments();
  const { data: scansData, isLoading: loadingScans, refetch: refetchScans } = useScans();
  const { data: records, isLoading: loadingRecords, refetch: refetchRecords } = useHealthRecords();
  const { data: doctors } = useDoctors();

  const handleRefresh = async () => {
    await Promise.all([refetchAppts(), refetchScans(), refetchRecords()]);
  };

  const isRefreshing = loadingAppts || loadingScans || loadingRecords;

  // Active / upcoming appointments
  const activeAppointments = (appointments?.filter((a: any) => a.status === 'pending' || a.status === 'approved' || a.status === 'confirmed') || [])
    .sort((a: any, b: any) => new Date(a.appointment_date).getTime() - new Date(b.appointment_date).getTime());
  
  // Scans history
  const scans = scansData?.data || [];
  const latestScan = scans.length > 0 ? scans[0] : null;

  // Latest Health Records / Vitals
  const sortedRecords = records ? [...records].sort((a: any, b: any) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()) : [];
  const latestRecord = sortedRecords.length > 0 ? sortedRecords[0] : null;
  const chartHistory = [...sortedRecords].reverse().slice(-6); // Last 6 records for recovery trend chart

  const [selectedVital, setSelectedVital] = React.useState<'score' | 'bp' | 'hr' | 'spo2' | 'temp' | 'weight'>('bp');
  const [showCharts, setShowCharts] = React.useState(false);

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={[styles.contentContainer, { paddingTop: Math.max(insets.top, 16) }]}
      refreshControl={
        <RefreshControl refreshing={isRefreshing} onRefresh={handleRefresh} colors={[orvellaColors.primary]} />
      }
    >
      {/* Top Divider Frame/Border line */}
      <View style={styles.topPageBorder} />

      {/* 1. Header Area with Patient Info & Logout */}
      <View style={styles.header}>
        <View style={styles.profileRow}>
          <View style={styles.avatarContainer}>
            <Text style={styles.avatarInitial}>
              {user?.full_name ? user.full_name.charAt(0).toUpperCase() : 'P'}
            </Text>
          </View>
          <View style={styles.profileTextWrapper}>
            <Text style={styles.welcomeText}>Welcome back,</Text>
            <Text style={styles.nameText} numberOfLines={1}>
              {user?.full_name || (user as any)?.name || 'Patient'}
            </Text>
          </View>
        </View>
        <TouchableOpacity style={styles.logoutBtn} onPress={logout} activeOpacity={0.7}>
          <Ionicons name="log-out-outline" size={20} color="#DC2626" />
        </TouchableOpacity>
      </View>

      {/* 2. Gradient Welcome Banner */}
      <View style={styles.welcomeBanner}>
        <View style={styles.bannerContent}>
          <View style={styles.bannerTextContainer}>
            <Text style={styles.bannerTitle}>Orvella Health</Text>
            <Text style={styles.bannerDesc}>
              Cervical Cancer analysis & real-time integrated medical task assignment system.
            </Text>
          </View>
          <View style={styles.bannerIconContainer}>
            <Ionicons name="pulse" size={54} color="rgba(255,255,255,0.2)" />
          </View>
        </View>
      </View>

      {/* 3. Upcoming Visit / Upcoming Consultation (Redesigned Mockup Card Style) */}
      <View style={styles.sectionHeader}>
        <Text style={styles.sectionTitle}>Upcoming Visit</Text>
        {activeAppointments.length > 0 && (
          <TouchableOpacity onPress={() => router.push('/(patient)/history')}>
            <Text style={styles.sectionActionText}>See All</Text>
          </TouchableOpacity>
        )}
      </View>

      {activeAppointments.length > 0 ? (
        <TouchableOpacity
          activeOpacity={0.9}
          onPress={() => router.push('/(patient)/history')}
          style={styles.upcomingVisitCard}
        >
          {/* Doctor Header Info Row */}
          <View style={styles.visitDoctorRow}>
            <View style={styles.visitAvatar}>
              <Text style={styles.visitAvatarText}>
                {activeAppointments[0].doctor_name ? activeAppointments[0].doctor_name.charAt(0).toUpperCase() : 'D'}
              </Text>
            </View>
            <View style={styles.visitDoctorMeta}>
              <Text style={styles.visitDoctorName}>Dr. {activeAppointments[0].doctor_name || 'Specialist'}</Text>
              <Text style={styles.visitDoctorSpecialty}>
                {doctors?.find((d: any) => d.id === activeAppointments[0].doctor_id)?.specialty || 'Pulmonologist & Respiratory Specialist'}
              </Text>
            </View>
          </View>

          {/* Date & Time Translucent Badge Container */}
          <View style={styles.visitBadgeContainer}>
            <View style={styles.visitBadgeItem}>
              <Ionicons name="calendar-outline" size={14} color="#ffffff" style={{ marginRight: 6 }} />
              <Text style={styles.visitBadgeLabel}>Date : </Text>
              <Text style={styles.visitBadgeValue}>
                {(() => {
                  const rawDate = activeAppointments[0].appointment_date ? activeAppointments[0].appointment_date.split('T')[0] : '';
                  const d = rawDate ? new Date(`${rawDate}T12:00:00Z`) : new Date();
                  return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
                })()}
              </Text>
            </View>

            <View style={styles.visitBadgeDivider} />

            <View style={styles.visitBadgeItem}>
              <Ionicons name="time-outline" size={14} color="#ffffff" style={{ marginRight: 6 }} />
              <Text style={styles.visitBadgeLabel}>Time : </Text>
              <Text style={styles.visitBadgeValue}>
                {(() => {
                  let startStr = '00:00';
                  if (activeAppointments[0].appointment_date) {
                    const timeMatch = activeAppointments[0].appointment_date.match(/T(\d{2}:\d{2})/);
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
        </TouchableOpacity>
      ) : (
        <Card style={styles.emptyCardCompact} variant="outlined">
          <Text style={styles.emptyTextCompact}>No upcoming consultations scheduled.</Text>
          <TouchableOpacity
            style={styles.compactBookBtn}
            onPress={() => router.push('/(patient)/appointments')}
          >
            <Text style={styles.compactBookBtnText}>Book Now</Text>
          </TouchableOpacity>
        </Card>
      )}

      {/* 4. Quick Actions Section (Moved Below Upcoming Visit) */}
      <View style={styles.sectionHeader}>
        <Text style={styles.sectionTitle}>Quick Services</Text>
      </View>
      <View style={styles.quickActionsGrid}>
        <TouchableOpacity
          style={styles.actionCard}
          onPress={() => router.push('/(patient)/appointments')}
          activeOpacity={0.8}
        >
          <Ionicons name="calendar-outline" size={18} color={orvellaColors.primary} />
          <Text style={styles.actionText}>Book Appointment</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.actionCard}
          onPress={() => router.push('/(patient)/scans')}
          activeOpacity={0.8}
        >
          <Ionicons name="scan-outline" size={18} color="#059669" />
          <Text style={styles.actionText}>Scan History</Text>
        </TouchableOpacity>
      </View>

      {/* 4. Latest Health Vitals */}
      <View style={styles.sectionHeader}>
        <Text style={styles.sectionTitle}>Latest Vital Signs</Text>
        {latestRecord && (
          <Text style={styles.sectionHeaderDate}>
            Updated: {new Date(latestRecord.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
          </Text>
        )}
      </View>

      {latestRecord ? (
        <View style={styles.vitalsGrid}>
          {/* BP */}
          <View style={[styles.vitalCard, { borderLeftColor: orvellaColors.primary }]}>
            <View style={styles.vitalHeader}>
              <View style={[styles.iconBadge, { backgroundColor: '#EEF3FF' }]}>
                <Ionicons name="heart-circle" size={16} color={orvellaColors.primary} />
              </View>
              <Text style={styles.vitalLabel}>Blood Pressure</Text>
            </View>
            <Text style={styles.vitalValue}>
              {latestRecord.systolic}/{latestRecord.diastolic}
            </Text>
            <Text style={styles.vitalUnit}>mmHg</Text>
          </View>

          {/* HR */}
          <View style={[styles.vitalCard, { borderLeftColor: orvellaColors.danger }]}>
            <View style={styles.vitalHeader}>
              <View style={[styles.iconBadge, { backgroundColor: '#FEF2F2' }]}>
                <Ionicons name="heart" size={14} color={orvellaColors.danger} />
              </View>
              <Text style={styles.vitalLabel}>Heart Rate</Text>
            </View>
            <Text style={styles.vitalValue}>{latestRecord.heart_rate}</Text>
            <Text style={styles.vitalUnit}>bpm</Text>
          </View>

          {/* SpO2 */}
          <View style={[styles.vitalCard, { borderLeftColor: orvellaColors.accent }]}>
            <View style={styles.vitalHeader}>
              <View style={[styles.iconBadge, { backgroundColor: '#ECFEFF' }]}>
                <Ionicons name="pulse" size={14} color={orvellaColors.accent} />
              </View>
              <Text style={styles.vitalLabel}>Oxygen (SpO2)</Text>
            </View>
            <Text style={styles.vitalValue}>{latestRecord.oxygen_level}</Text>
            <Text style={styles.vitalUnit}>%</Text>
          </View>

          {/* Temp */}
          <View style={[styles.vitalCard, { borderLeftColor: orvellaColors.warning }]}>
            <View style={styles.vitalHeader}>
              <View style={[styles.iconBadge, { backgroundColor: '#FFFBEB' }]}>
                <Ionicons name="thermometer" size={14} color={orvellaColors.warning} />
              </View>
              <Text style={styles.vitalLabel}>Body Temp</Text>
            </View>
            <Text style={styles.vitalValue}>{latestRecord.temperature}</Text>
            <Text style={styles.vitalUnit}>°C</Text>
          </View>
        </View>
      ) : (
        <Card style={styles.emptyVitalsCard}>
          <Ionicons name="pulse-outline" size={32} color={orvellaColors.textMuted} />
          <Text style={styles.emptyVitalsText}>
            No vital signs records registered from Orvella clinic yet.
          </Text>
        </Card>
      )}

      {/* Health History & Charts */}
      <View style={styles.sectionHeader}>
        <Text style={styles.sectionTitle}>Health History & Vitals Charts</Text>
      </View>

      <View style={styles.expandedChartsContainer}>
          {/* Metric Selector Tabs */}
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            style={styles.tabsContainer}
            contentContainerStyle={styles.tabsContent}
          >
            {[
              { key: 'bp', label: 'Blood Pressure', icon: 'heart-circle', color: orvellaColors.primary },
              { key: 'hr', label: 'Heart Rate', icon: 'heart', color: orvellaColors.danger },
              { key: 'spo2', label: 'Oxygen (SpO2)', icon: 'pulse', color: orvellaColors.accent },
              { key: 'temp', label: 'Body Temperature', icon: 'thermometer', color: orvellaColors.warning },
              { key: 'weight', label: 'Weight', icon: 'scale', color: '#6366f1' },
              { key: 'score', label: 'Health Score', icon: 'trending-up', color: orvellaColors.success },
            ].map((tab) => {
              const isActive = selectedVital === tab.key;
              return (
                <TouchableOpacity
                  key={tab.key}
                  style={[
                    styles.tabButton,
                    isActive && { backgroundColor: tab.color + '15', borderColor: tab.color }
                  ]}
                  onPress={() => setSelectedVital(tab.key as any)}
                >
                  <Ionicons name={tab.icon as any} size={15} color={isActive ? tab.color : orvellaColors.textSecondary} />
                  <Text style={[styles.tabButtonText, isActive && { color: tab.color, fontWeight: 'bold' }]}>
                    {tab.label}
                  </Text>
                </TouchableOpacity>
              );
            })}
          </ScrollView>

          {chartHistory.length > 0 ? (() => {
            const chartWidth = 320;
            const chartHeight = 150;
            const paddingLeft = 32;
            const paddingRight = 15;
            const paddingTop = 20;
            const paddingBottom = 25;

            const usableWidth = chartWidth - paddingLeft - paddingRight;
            const usableHeight = chartHeight - paddingTop - paddingBottom;

            // Dynamic scale boundaries based on selected metric
            let minVal = 0;
            let maxVal = 100;
            let unit = '';
            let title = '';
            let activeColor = orvellaColors.primary;

            if (selectedVital === 'score') {
              minVal = 0;
              maxVal = 100;
              unit = '';
              title = 'Recovery Trend (Health Score)';
              activeColor = orvellaColors.success;
            } else if (selectedVital === 'bp') {
              const systolics = chartHistory.map((item: any) => item.systolic || 120);
              const diastolics = chartHistory.map((item: any) => item.diastolic || 80);
              const allBP = [...systolics, ...diastolics];
              const minBP = Math.min(...allBP);
              const maxBP = Math.max(...allBP);
              minVal = Math.max(0, Math.floor(minBP / 10) * 10 - 20);
              maxVal = Math.ceil(maxBP / 10) * 10 + 20;
              unit = ' mmHg';
              title = 'Blood Pressure (Systolic/Diastolic)';
              activeColor = orvellaColors.primary;
            } else if (selectedVital === 'hr') {
              const hrs = chartHistory.map((item: any) => item.heart_rate || 75);
              const minHR = Math.min(...hrs);
              const maxHR = Math.max(...hrs);
              minVal = Math.max(0, Math.floor(minHR / 10) * 10 - 15);
              maxVal = Math.ceil(maxHR / 10) * 10 + 15;
              unit = ' bpm';
              title = 'Heart Rate (Pulse)';
              activeColor = orvellaColors.danger;
            } else if (selectedVital === 'spo2') {
              const spo2s = chartHistory.map((item: any) => item.oxygen_level || 98);
              const minSpO2 = Math.min(...spo2s);
              const maxSpO2 = Math.max(...spo2s);
              minVal = Math.max(0, Math.floor(minSpO2) - 4);
              maxVal = Math.min(100, Math.ceil(maxSpO2) + 2);
              unit = '%';
              title = 'Oxygen Saturation (SpO2)';
              activeColor = orvellaColors.accent;
            } else if (selectedVital === 'temp') {
              const temps = chartHistory.map((item: any) => item.temperature || 36.5);
              const minTemp = Math.min(...temps);
              const maxTemp = Math.max(...temps);
              minVal = Math.max(0, Math.floor(minTemp) - 1);
              maxVal = Math.ceil(maxTemp) + 1;
              unit = '°C';
              title = 'Body Temperature';
              activeColor = orvellaColors.warning;
            } else if (selectedVital === 'weight') {
              const weights = chartHistory.map((item: any) => item.weight || 70);
              const minW = Math.min(...weights);
              const maxW = Math.max(...weights);
              minVal = Math.max(0, Math.floor(minW / 5) * 5 - 10);
              maxVal = Math.ceil(maxW / 5) * 5 + 10;
              unit = ' kg';
              title = 'Body Weight';
              activeColor = '#6366f1';
            }

            const range = maxVal - minVal || 1;

            // Build data points
            const points = chartHistory.map((item: any, index: number) => {
              const x = paddingLeft + (index / Math.max(1, chartHistory.length - 1)) * usableWidth;
              const date = new Date(item.created_at).toLocaleDateString('en-US', { day: 'numeric', month: 'short' });

              if (selectedVital === 'bp') {
                const sysVal = item.systolic || 120;
                const diaVal = item.diastolic || 80;
                const ySys = paddingTop + usableHeight - ((sysVal - minVal) / range) * usableHeight;
                const yDia = paddingTop + usableHeight - ((diaVal - minVal) / range) * usableHeight;
                return { x, ySys, yDia, sysVal, diaVal, date };
              } else {
                let val = 0;
                if (selectedVital === 'score') val = item.health_score || 0;
                else if (selectedVital === 'hr') val = item.heart_rate || 0;
                else if (selectedVital === 'spo2') val = item.oxygen_level || 0;
                else if (selectedVital === 'temp') val = item.temperature || 0;
                else if (selectedVital === 'weight') val = item.weight || 0;

                const y = paddingTop + usableHeight - ((val - minVal) / range) * usableHeight;
                return { x, y, val, date };
              }
            });

            // Path generators
            let primaryLinePath = '';
            let primaryAreaPath = '';
            let secondaryLinePath = '';

            if (selectedVital === 'bp' && points.length > 0) {
              primaryLinePath = `M ${points[0].x} ${points[0].ySys} ` + points.slice(1).map(p => `L ${p.x} ${p.ySys}`).join(' ');
              secondaryLinePath = `M ${points[0].x} ${points[0].yDia} ` + points.slice(1).map(p => `L ${p.x} ${p.yDia}`).join(' ');
            } else if (points.length > 0) {
              primaryLinePath = `M ${points[0].x} ${points[0].y} ` + points.slice(1).map(p => `L ${p.x} ${p.y}`).join(' ');
              primaryAreaPath = `${primaryLinePath} L ${points[points.length - 1].x} ${paddingTop + usableHeight} L ${points[0].x} ${paddingTop + usableHeight} Z`;
            }

            // Generate Y-axis grid values (4 labels)
            const gridVals = [
              maxVal,
              minVal + range * 0.66,
              minVal + range * 0.33,
              minVal
            ];

            return (
              <Card style={styles.chartCard} variant="outlined">
                <View style={{ marginBottom: 8, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                  <Text style={{ fontSize: 11, fontWeight: 'bold', color: orvellaColors.textPrimary }}>{title}</Text>
                  {selectedVital === 'bp' && (
                    <View style={{ flexDirection: 'row', gap: 8 }}>
                      <View style={{ flexDirection: 'row', alignItems: 'center', gap: 3 }}>
                        <View style={{ width: 8, height: 8, borderRadius: 4, backgroundColor: orvellaColors.primary }} />
                        <Text style={{ fontSize: 9, color: orvellaColors.textSecondary }}>Systolic</Text>
                      </View>
                      <View style={{ flexDirection: 'row', alignItems: 'center', gap: 3 }}>
                        <View style={{ width: 8, height: 8, borderRadius: 4, backgroundColor: orvellaColors.accent }} />
                        <Text style={{ fontSize: 9, color: orvellaColors.textSecondary }}>Diastolic</Text>
                      </View>
                    </View>
                  )}
                </View>
                <View style={{ alignItems: 'center' }}>
                  <Svg width={chartWidth} height={chartHeight}>
                    <Defs>
                      <LinearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
                        <Stop offset="0%" stopColor={activeColor} stopOpacity="0.35" />
                        <Stop offset="100%" stopColor={activeColor} stopOpacity="0.0" />
                      </LinearGradient>
                    </Defs>

                    {/* Grid Lines */}
                    {gridVals.map((val, idx) => {
                      const y = paddingTop + usableHeight - ((val - minVal) / range) * usableHeight;
                      return (
                        <G key={idx}>
                          <Line
                            x1={paddingLeft}
                            y1={y}
                            x2={chartWidth - paddingRight}
                            y2={y}
                            stroke={orvellaColors.border}
                            strokeWidth="0.8"
                            strokeDasharray="4 4"
                          />
                          <SvgText
                            x={paddingLeft - 6}
                            y={y + 3}
                            fontSize="8"
                            fill={orvellaColors.textSecondary}
                            textAnchor="end"
                            fontWeight="bold"
                          >
                            {Math.round(val)}
                          </SvgText>
                        </G>
                      );
                    })}

                    {/* Solid X and Y Axis Lines */}
                    <Line
                      x1={paddingLeft}
                      y1={paddingTop}
                      x2={paddingLeft}
                      y2={paddingTop + usableHeight}
                      stroke={orvellaColors.textSecondary}
                      strokeWidth="1.2"
                    />
                    <Line
                      x1={paddingLeft}
                      y1={paddingTop + usableHeight}
                      x2={chartWidth - paddingRight}
                      y2={paddingTop + usableHeight}
                      stroke={orvellaColors.textSecondary}
                      strokeWidth="1.2"
                    />

                    {/* Area under the line */}
                    {selectedVital !== 'bp' && primaryAreaPath ? <Path d={primaryAreaPath} fill="url(#chartGrad)" /> : null}

                    {/* Trend lines */}
                    {selectedVital === 'bp' ? (
                      <>
                        <Path d={primaryLinePath} fill="none" stroke={orvellaColors.primary} strokeWidth="2.5" />
                        <Path d={secondaryLinePath} fill="none" stroke={orvellaColors.accent} strokeWidth="2.5" />
                      </>
                    ) : primaryLinePath ? (
                      <Path d={primaryLinePath} fill="none" stroke={activeColor} strokeWidth="2.5" />
                    ) : null}

                    {/* Data Circles & Value Labels */}
                    {points.map((p: any, idx: number) => {
                      if (selectedVital === 'bp') {
                        return (
                          <G key={idx}>
                            <Circle cx={p.x} cy={p.ySys} r="3" fill="#ffffff" stroke={orvellaColors.primary} strokeWidth="2" />
                            <SvgText x={p.x} y={p.ySys - 6} fontSize="8" fill={orvellaColors.textPrimary} fontWeight="bold" textAnchor="middle">{p.sysVal}</SvgText>
                            <Circle cx={p.x} cy={p.yDia} r="3" fill="#ffffff" stroke={orvellaColors.accent} strokeWidth="2" />
                            <SvgText x={p.x} y={p.yDia + 10} fontSize="8" fill={orvellaColors.textPrimary} fontWeight="bold" textAnchor="middle">{p.diaVal}</SvgText>
                            <SvgText x={p.x} y={paddingTop + usableHeight + 14} fontSize="8" fill={orvellaColors.textSecondary} textAnchor="middle" fontWeight="500">{p.date}</SvgText>
                          </G>
                        );
                      } else {
                        return (
                          <G key={idx}>
                            <Circle cx={p.x} cy={p.y} r="3.5" fill="#ffffff" stroke={activeColor} strokeWidth="2" />
                            <SvgText x={p.x} y={p.y - 7} fontSize="8.5" fill={orvellaColors.textPrimary} fontWeight="bold" textAnchor="middle">{p.val}{unit}</SvgText>
                            <SvgText x={p.x} y={paddingTop + usableHeight + 14} fontSize="8" fill={orvellaColors.textSecondary} textAnchor="middle" fontWeight="500">{p.date}</SvgText>
                          </G>
                        );
                      }
                    })}
                  </Svg>
                </View>
              </Card>
            );
          })() : (
            <Card style={styles.emptyChartCard} variant="outlined">
              <View style={{ alignItems: 'center', width: '100%' }}>
                <Svg width="300" height="90">
                  <Rect
                    x="2"
                    y="2"
                    width="296"
                    height="86"
                    rx="8"
                    fill="none"
                    stroke={orvellaColors.border}
                    strokeWidth="1.5"
                    strokeDasharray="6 6"
                  />
                  <Line x1="20" y1="60" x2="100" y2="50" stroke={orvellaColors.border} strokeWidth="2" strokeDasharray="4 4" />
                  <Line x1="100" y1="50" x2="180" y2="65" stroke={orvellaColors.border} strokeWidth="2" strokeDasharray="4 4" />
                  <Line x1="180" y1="65" x2="280" y2="30" stroke={orvellaColors.border} strokeWidth="2" strokeDasharray="4 4" />
                </Svg>
                <Ionicons name="stats-chart-outline" size={28} color={orvellaColors.textMuted} style={{ opacity: 0.5, marginTop: 12 }} />
                <Text style={styles.emptyChartTitle}>No Health Tracking History Yet</Text>
                <Text style={styles.emptyChartDesc}>
                  Vital signs checkups at the Orvella clinic will automatically build your recovery tracking chart here.
                </Text>
              </View>
            </Card>
          )}
        </View>



      {/* 6. Latest Cervical Scan (Compact Layout) */}
      <View style={styles.sectionHeader}>
        <Text style={styles.sectionTitle}>Latest Examination Scan</Text>
        {scans.length > 0 && (
          <TouchableOpacity onPress={() => router.push('/(patient)/scans')}>
            <Text style={styles.sectionActionText}>See All</Text>
          </TouchableOpacity>
        )}
      </View>

      {latestScan ? (
        <TouchableOpacity
          activeOpacity={0.8}
          onPress={() => router.push('/(patient)/scans')}
          style={styles.compactListRow}
        >
          <View style={[styles.avatarMiniBadge, { backgroundColor: '#ECFDF5' }]}>
            <Ionicons name="pulse" size={16} color="#059669" />
          </View>
          <View style={{ flex: 1, marginLeft: 10 }}>
            <Text style={styles.doctorNameCompact}>Cervical Cancer Screening</Text>
            <Text style={styles.doctorSpecialtyCompact}>
              {new Date(latestScan.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
            </Text>
          </View>
          <StatusBadge status={latestScan.status} />
        </TouchableOpacity>
      ) : (
        <Card style={styles.emptyCardCompact} variant="outlined">
          <Text style={styles.emptyTextCompact}>No scan files available yet.</Text>
        </Card>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: orvellaColors.background,
  },
  contentContainer: {
    paddingHorizontal: orvellaSpacing.md,
    paddingTop: orvellaSpacing.md,
    paddingBottom: orvellaSpacing.xxl,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: orvellaSpacing.md,
    marginTop: orvellaSpacing.sm,
  },
  profileRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: orvellaSpacing.sm,
  },
  avatarContainer: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: orvellaColors.primary,
    justifyContent: 'center',
    alignItems: 'center',
    ...orvellaShadow.sm,
  },
  avatarInitial: {
    color: '#ffffff',
    fontSize: 18,
    fontWeight: 'bold',
  },
  profileTextWrapper: {
    flexDirection: 'column',
    justifyContent: 'center',
  },
  welcomeText: {
    fontSize: 11,
    color: '#64748B',
    fontWeight: '500',
  },
  nameText: {
    fontSize: 18,
    fontWeight: '800',
    color: '#0F172A',
  },
  logoutBtn: {
    width: 42,
    height: 42,
    borderRadius: 21,
    backgroundColor: '#FEF2F2',
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#FEE2E2',
  },
  welcomeBanner: {
    backgroundColor: orvellaColors.primary,
    padding: 20,
    borderRadius: 18,
    marginBottom: 20,
    position: 'relative',
    overflow: 'hidden',
    ...orvellaShadow.md,
  },
  bannerContent: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  bannerTextContainer: {
    flex: 1,
    paddingRight: 12,
  },
  bannerTitle: {
    fontSize: 22,
    fontWeight: '800',
    color: '#ffffff',
    marginBottom: 6,
    letterSpacing: 0.3,
  },
  bannerDesc: {
    fontSize: 12,
    color: '#E0F2FE',
    lineHeight: 18,
    fontWeight: '500',
  },
  bannerIconContainer: {
    justifyContent: 'center',
    alignItems: 'center',
    opacity: 0.85,
  },
  sectionTitle: {
    fontSize: 15,
    fontWeight: '700',
    color: '#1E293B',
    marginBottom: 10,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
    marginTop: 20,
  },
  sectionHeaderDate: {
    fontSize: 11,
    color: '#64748B',
    fontWeight: '600',
  },
  sectionActionText: {
    fontSize: 12,
    fontWeight: '700',
    color: orvellaColors.primary,
  },
  quickActionsGrid: {
    flexDirection: 'row',
    gap: 12,
    marginBottom: 20,
  },
  actionCard: {
    flex: 1,
    backgroundColor: '#ffffff',
    borderRadius: 16,
    padding: 16,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#F1F5F9',
    ...orvellaShadow.sm,
  },
  actionIconContainer: {
    width: 50,
    height: 50,
    borderRadius: 25,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 10,
  },
  actionText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#1E293B',
    textAlign: 'center',
  },
  vitalsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    gap: 10,
    marginBottom: 12,
  },
  vitalCard: {
    width: '48%',
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 10,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    borderLeftWidth: 3,
    ...orvellaShadow.sm,
  },
  vitalHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    marginBottom: 4,
  },
  iconBadge: {
    width: 20,
    height: 20,
    borderRadius: 10,
    justifyContent: 'center',
    alignItems: 'center',
  },
  vitalLabel: {
    fontSize: 10,
    fontWeight: '700',
    color: '#64748B',
  },
  vitalValue: {
    fontSize: 16,
    fontWeight: '800',
    color: '#0F172A',
  },
  vitalUnit: {
    fontSize: 9,
    color: '#64748B',
    fontWeight: '600',
    marginTop: 1,
  },
  emptyVitalsCard: {
    alignItems: 'center',
    justifyContent: 'center',
    padding: orvellaSpacing.md,
    backgroundColor: orvellaColors.surface,
    gap: 8,
    marginBottom: orvellaSpacing.xs,
  },
  emptyVitalsText: {
    fontSize: orvellaFontSize.xs,
    color: orvellaColors.textSecondary,
    textAlign: 'center',
    lineHeight: 18,
  },
  apptCard: {
    padding: orvellaSpacing.md,
    ...orvellaShadow.sm,
  },
  apptHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  apptDoctorInfo: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: orvellaSpacing.sm,
  },
  apptDoctorAvatar: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: orvellaColors.primaryLight,
    justifyContent: 'center',
    alignItems: 'center',
  },
  doctorName: {
    fontSize: orvellaFontSize.sm,
    fontWeight: 'bold',
    color: orvellaColors.textPrimary,
  },
  doctorSpecialty: {
    fontSize: 10,
    color: orvellaColors.textSecondary,
  },
  apptDivider: {
    height: 1,
    backgroundColor: orvellaColors.border,
    marginVertical: orvellaSpacing.sm,
  },
  apptFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  apptInfoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  apptInfoText: {
    fontSize: orvellaFontSize.xs,
    color: orvellaColors.textSecondary,
  },
  scanCard: {
    padding: orvellaSpacing.md,
    ...orvellaShadow.sm,
  },
  scanHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderBottomWidth: 1,
    borderBottomColor: orvellaColors.border,
    paddingBottom: orvellaSpacing.sm,
    marginBottom: orvellaSpacing.sm,
  },
  scanTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  scanIconWrapper: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: orvellaColors.primaryLight,
    justifyContent: 'center',
    alignItems: 'center',
  },
  scanTitle: {
    fontSize: orvellaFontSize.sm,
    fontWeight: 'bold',
    color: orvellaColors.textPrimary,
  },
  scanDate: {
    fontSize: 10,
    color: orvellaColors.textSecondary,
    marginTop: 2,
  },
  aiResultContainer: {
    backgroundColor: orvellaColors.surfaceVariant,
    borderRadius: orvellaRadius.md,
    padding: orvellaSpacing.sm,
  },
  aiResultHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 8,
  },
  aiResultTitle: {
    fontSize: 12,
    fontWeight: 'bold',
    color: orvellaColors.primaryDark,
  },
  aiPredictionBlock: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 4,
  },
  aiLabel: {
    fontSize: orvellaFontSize.xs,
    color: orvellaColors.textSecondary,
  },
  aiValue: {
    fontSize: orvellaFontSize.xs,
    fontWeight: 'bold',
    color: orvellaColors.textPrimary,
  },
  confidenceRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 4,
  },
  confidenceLabel: {
    fontSize: orvellaFontSize.xs,
    color: orvellaColors.textSecondary,
  },
  confidenceValue: {
    fontSize: orvellaFontSize.xs,
    fontWeight: 'bold',
    color: orvellaColors.primary,
  },
  confidenceBarBg: {
    height: 6,
    backgroundColor: orvellaColors.border,
    borderRadius: 3,
    marginTop: 4,
    overflow: 'hidden',
  },
  confidenceBarFill: {
    height: '100%',
    borderRadius: 3,
  },
  aiPendingContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    backgroundColor: orvellaColors.primaryLight,
    padding: orvellaSpacing.sm,
    borderRadius: orvellaRadius.md,
  },
  aiPendingText: {
    fontSize: 11,
    color: orvellaColors.primaryDark,
  },
  emptyCard: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: orvellaSpacing.lg,
    paddingHorizontal: orvellaSpacing.md,
    backgroundColor: orvellaColors.surface,
    gap: 8,
  },
  emptyText: {
    fontSize: orvellaFontSize.sm,
    color: orvellaColors.textSecondary,
    textAlign: 'center',
  },
  emptyActionBtn: {
    marginTop: 4,
    minHeight: 36,
  },
  chartCard: {
    padding: orvellaSpacing.md,
    backgroundColor: '#ffffff',
    borderRadius: orvellaRadius.md,
    marginBottom: orvellaSpacing.md,
  },
  chartContainer: {
    height: 180,
    justifyContent: 'flex-end',
    position: 'relative',
    paddingTop: orvellaSpacing.md,
  },
  chartGrid: {
    position: 'absolute',
    left: 0,
    right: 0,
    top: orvellaSpacing.md,
    bottom: 30,
    justifyContent: 'space-between',
  },
  chartGridLine: {
    height: 1,
    backgroundColor: orvellaColors.border,
    width: '100%',
  },
  chartBarsRow: {
    flexDirection: 'row',
    justifyContent: 'space-around',
    alignItems: 'flex-end',
    height: '100%',
    zIndex: 2,
    paddingBottom: 20,
  },
  chartBarWrapper: {
    alignItems: 'center',
    width: '15%',
    height: '100%',
    justifyContent: 'flex-end',
  },
  chartBarValueContainer: {
    marginBottom: 4,
    backgroundColor: orvellaColors.surfaceVariant,
    paddingHorizontal: 4,
    paddingVertical: 2,
    borderRadius: orvellaRadius.sm,
  },
  chartBarValueText: {
    fontSize: 9,
    fontWeight: 'bold',
    color: orvellaColors.textPrimary,
  },
  chartBarFill: {
    width: 14,
    borderTopLeftRadius: 6,
    borderTopRightRadius: 6,
    minHeight: 10,
  },
  chartBarLabelText: {
    fontSize: 8,
    color: orvellaColors.textSecondary,
    marginTop: 6,
    fontWeight: '500',
    position: 'absolute',
    bottom: -15,
  },
  emptyChartCard: {
    padding: orvellaSpacing.lg,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#ffffff',
    borderRadius: orvellaRadius.md,
    borderWidth: 1.5,
    borderColor: orvellaColors.border,
    marginBottom: orvellaSpacing.md,
    gap: 4,
  },
  emptyChartTitle: {
    fontSize: orvellaFontSize.sm,
    fontWeight: 'bold',
    color: orvellaColors.textPrimary,
    marginTop: 6,
  },
  emptyChartDesc: {
    fontSize: 10,
    color: orvellaColors.textSecondary,
    textAlign: 'center',
    lineHeight: 14,
    paddingHorizontal: orvellaSpacing.md,
  },
  tabsContainer: {
    marginBottom: orvellaSpacing.sm,
  },
  tabsContent: {
    gap: 8,
    paddingRight: orvellaSpacing.md,
  },
  tabButton: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: orvellaRadius.md,
    borderWidth: 1,
    borderColor: orvellaColors.border,
    backgroundColor: '#ffffff',
  },
  tabButtonText: {
    fontSize: 11,
    color: orvellaColors.textSecondary,
  },
  topPageBorder: {
    height: 1,
    backgroundColor: '#E2E8F0',
    marginHorizontal: 16,
    marginTop: 8,
    marginBottom: 12,
  },
  collapsibleCard: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#ffffff',
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 16,
    ...orvellaShadow.sm,
  },
  collapsibleHeaderLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  collapsibleTitle: {
    fontSize: 13,
    fontWeight: '700',
    color: '#1E293B',
  },
  expandedChartsContainer: {
    marginBottom: 16,
  },
  compactListRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderRadius: 14,
    padding: 12,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    marginBottom: 10,
    ...orvellaShadow.sm,
  },
  avatarMiniBadge: {
    width: 36,
    height: 36,
    borderRadius: 18,
    justifyContent: 'center',
    alignItems: 'center',
  },
  doctorNameCompact: {
    fontSize: 13,
    fontWeight: '700',
    color: '#1E293B',
  },
  doctorSpecialtyCompact: {
    fontSize: 11,
    color: '#64748B',
    marginTop: 2,
  },
  emptyCardCompact: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#F8FAFC',
    borderRadius: 12,
    padding: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 10,
  },
  emptyTextCompact: {
    fontSize: 12,
    color: '#64748B',
    fontWeight: '500',
  },
  compactBookBtn: {
    backgroundColor: orvellaColors.primary,
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
  },
  compactBookBtnText: {
    color: '#ffffff',
    fontSize: 11,
    fontWeight: '700',
  },
  upcomingVisitCard: {
    backgroundColor: orvellaColors.primary,
    borderRadius: 20,
    padding: 16,
    marginBottom: 16,
    ...orvellaShadow.md,
  },
  visitDoctorRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  visitAvatar: {
    width: 46,
    height: 46,
    borderRadius: 23,
    backgroundColor: '#ffffff',
    justifyContent: 'center',
    alignItems: 'center',
  },
  visitAvatarText: {
    fontSize: 18,
    fontWeight: '800',
    color: orvellaColors.primary,
  },
  visitDoctorMeta: {
    flex: 1,
  },
  visitDoctorName: {
    fontSize: 15,
    fontWeight: '700',
    color: '#ffffff',
  },
  visitDoctorSpecialty: {
    fontSize: 11,
    color: '#E0F2FE',
    marginTop: 2,
  },
  visitVideoIcon: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: 'rgba(255, 255, 255, 0.15)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  visitBadgeContainer: {
    flexDirection: 'row',
    backgroundColor: 'rgba(0, 0, 0, 0.15)',
    borderRadius: 12,
    paddingVertical: 10,
    paddingHorizontal: 12,
    marginTop: 14,
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  visitBadgeItem: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  visitBadgeLabel: {
    fontSize: 11,
    color: 'rgba(255, 255, 255, 0.7)',
    fontWeight: '500',
  },
  visitBadgeValue: {
    fontSize: 11,
    color: '#ffffff',
    fontWeight: '700',
  },
  visitBadgeDivider: {
    width: 1,
    height: 14,
    backgroundColor: 'rgba(255, 255, 255, 0.3)',
    marginHorizontal: 8,
  },
});
