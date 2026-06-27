import React, { useState, useMemo } from 'react';
import {
  FlatList,
  StyleSheet,
  Text,
  View,
  TouchableOpacity,
  TextInput,
  ScrollView,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Ionicons } from '@expo/vector-icons';
import { usePatients } from '../../hooks/useApi';
import { useAuthStore } from '../../store/auth.store';
import { orvellaColors, orvellaFontSize, orvellaSpacing, orvellaRadius, orvellaShadow } from '../../constants/orvella';
import { LoadingState, EmptyState } from '../../components/ui/StateViews';
import { router } from 'expo-router';

function getInitials(name: string) {
  return name
    .split(' ')
    .map((w) => w[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();
}

const AVATAR_COLORS = ['#4F46E5', '#0891B2', '#059669', '#D97706', '#DC2626', '#7C3AED'];

function avatarColor(id: number) {
  return AVATAR_COLORS[id % AVATAR_COLORS.length];
}

export default function MedrecPatientsScreen() {
  const { user } = useAuthStore();
  const insets = useSafeAreaInsets();
  const { data: patients, isLoading, refetch } = usePatients();
  const [searchQuery, setSearchQuery] = useState('');
  const [genderFilter, setGenderFilter] = useState<'all' | 'male' | 'female'>('all');

  const processedPatients = useMemo(() => {
    let list = [...(patients || [])];

    // Search
    if (searchQuery.trim() !== '') {
      list = list.filter((p: any) =>
        p.name?.toLowerCase().includes(searchQuery.toLowerCase())
      );
    }

    // Gender Filter
    if (genderFilter === 'male') {
      list = list.filter((p: any) => p.gender?.toLowerCase() === 'male');
    } else if (genderFilter === 'female') {
      list = list.filter((p: any) => p.gender?.toLowerCase() === 'female');
    }

    // Default Sort (Newest first)
    list.sort((a, b) => b.id - a.id);

    return list;
  }, [patients, searchQuery, genderFilter]);

  if (isLoading) {
    return <LoadingState message="Loading patients data..." />;
  }

  return (
    <View style={styles.container}>
      <View style={[styles.topAppBar, { paddingTop: Math.max(insets.top, 16) }]}>
        <View style={styles.appBarLeft}>
          <Text style={styles.appBarTitle}>Patient Directory</Text>
        </View>
        <View style={styles.appBarRight}>
          <View style={styles.avatarMini}>
            <Text style={styles.avatarMiniText}>{user?.full_name ? user.full_name.charAt(0).toUpperCase() : 'U'}</Text>
          </View>
        </View>
      </View>

      {/* Search Bar */}
      <View style={styles.searchBar}>
        <Ionicons name="search" size={18} color="#9CA3AF" style={styles.searchIcon} />
        <TextInput
          style={styles.searchInput}
          placeholder="Search patients by name..."
          placeholderTextColor="#9CA3AF"
          value={searchQuery}
          onChangeText={setSearchQuery}
        />
        {searchQuery.length > 0 && (
          <TouchableOpacity onPress={() => setSearchQuery('')}>
            <Ionicons name="close-circle" size={18} color="#9CA3AF" />
          </TouchableOpacity>
        )}
      </View>

      {/* Filter Bar */}
      <View style={styles.sortBar}>
        <Text style={styles.sortLabel}>Filter:</Text>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.sortOptionsRow}>
          <TouchableOpacity 
            style={[styles.sortOption, genderFilter === 'all' && styles.activeSortOption]} 
            onPress={() => setGenderFilter('all')}
            activeOpacity={0.8}
          >
            <Text style={[styles.sortOptionText, genderFilter === 'all' && styles.activeSortOptionText]}>All</Text>
          </TouchableOpacity>
          <TouchableOpacity 
            style={[styles.sortOption, genderFilter === 'male' && styles.activeSortOption]} 
            onPress={() => setGenderFilter('male')}
            activeOpacity={0.8}
          >
            <Text style={[styles.sortOptionText, genderFilter === 'male' && styles.activeSortOptionText]}>Male</Text>
          </TouchableOpacity>
          <TouchableOpacity 
            style={[styles.sortOption, genderFilter === 'female' && styles.activeSortOption]} 
            onPress={() => setGenderFilter('female')}
            activeOpacity={0.8}
          >
            <Text style={[styles.sortOptionText, genderFilter === 'female' && styles.activeSortOptionText]}>Female</Text>
          </TouchableOpacity>
        </ScrollView>
      </View>

      <View style={styles.directoryStatsBanner}>
        <Ionicons name="people" size={16} color={orvellaColors.primary} />
        <Text style={styles.directoryStatsText}>{processedPatients.length} Patients Found</Text>
      </View>

      <FlatList
        data={processedPatients}
        keyExtractor={(item) => item.id.toString()}
        refreshing={isLoading}
        onRefresh={refetch}
        ListEmptyComponent={<EmptyState title="No registered patients found." />}
        contentContainerStyle={styles.listContainer}
        renderItem={({ item }) => {
          const formattedDob = item.dob
            ? new Date(item.dob).toLocaleDateString('en-US', {
                day: 'numeric', month: 'short', year: 'numeric',
              })
            : 'No date of birth';

          return (
            <View style={styles.card}>
              <View style={[styles.avatar, { backgroundColor: avatarColor(item.id) }]}>
                <Text style={styles.avatarText}>{getInitials(item.name || 'P')}</Text>
              </View>

              <View style={styles.patientDetails}>
                <Text style={styles.patientName}>{item.name}</Text>
                
                {/* Meta row: Gender | DOB */}
                <View style={styles.metaRow}>
                  <View style={[
                    styles.genderPill,
                    { backgroundColor: item.gender?.toLowerCase() === 'female' ? '#FDF2F8' : '#EFF6FF' }
                  ]}>
                    <Ionicons
                      name={item.gender?.toLowerCase() === 'female' ? 'female' : 'male'}
                      size={10}
                      color={item.gender?.toLowerCase() === 'female' ? '#DB2777' : '#2563EB'}
                    />
                    <Text style={[
                      styles.genderText,
                      { color: item.gender?.toLowerCase() === 'female' ? '#DB2777' : '#2563EB' }
                    ]}>
                      {item.gender || 'Unknown'}
                    </Text>
                  </View>
                  <Text style={styles.bulletSeparator}>•</Text>
                  <Text style={styles.metaText}>{formattedDob}</Text>
                </View>

                {/* Sub info row: Phone & Emergency Contact */}
                <View style={styles.subInfoRow}>
                  <View style={styles.infoPill}>
                    <Ionicons name="call-outline" size={12} color="#64748B" />
                    <Text style={styles.infoPillText}>{item.phone || '-'}</Text>
                  </View>
                  {item.emergency_contact ? (
                    <View style={[styles.infoPill, styles.emergencyPill]}>
                      <Ionicons name="alert-circle-outline" size={12} color="#DC2626" />
                      <Text style={[styles.infoPillText, styles.emergencyPillText]}>
                        EMG: {item.emergency_contact}
                      </Text>
                    </View>
                  ) : null}
                </View>
              </View>
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
  directoryStatsBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginHorizontal: 16,
    paddingHorizontal: 12,
    paddingVertical: 8,
    backgroundColor: '#EEF3FF',
    borderRadius: 8,
    marginBottom: 8,
  },
  directoryStatsText: {
    fontSize: 12,
    fontWeight: '600',
    color: orvellaColors.primary,
  },
  listContainer: {
    paddingHorizontal: 16,
    paddingTop: 4,
    paddingBottom: 24,
  },
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderRadius: 14,
    marginBottom: 10,
    padding: 12,
    borderWidth: 1,
    borderColor: '#F1F5F9',
    ...orvellaShadow.sm,
  },
  avatar: {
    width: 48,
    height: 48,
    borderRadius: 24,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
    flexShrink: 0,
  },
  avatarText: {
    color: '#fff',
    fontSize: orvellaFontSize.md,
    fontWeight: '700',
  },
  patientDetails: {
    flex: 1,
  },
  patientName: {
    fontSize: orvellaFontSize.md,
    fontWeight: '700',
    color: orvellaColors.textPrimary,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 3,
    marginBottom: 6,
  },
  genderPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 3,
    paddingHorizontal: 6,
    paddingVertical: 1.5,
    borderRadius: 6,
  },
  genderText: {
    fontSize: 10,
    fontWeight: '700',
    textTransform: 'capitalize',
  },
  bulletSeparator: {
    marginHorizontal: 6,
    color: '#94A3B8',
    fontSize: 10,
  },
  metaText: {
    fontSize: 12,
    color: '#64748B',
    fontWeight: '500',
  },
  subInfoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    flexWrap: 'wrap',
  },
  infoPill: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#F8FAFC',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  infoPillText: {
    fontSize: 11,
    color: '#64748B',
    fontWeight: '600',
  },
  emergencyPill: {
    backgroundColor: '#FEF2F2',
    borderColor: '#FEE2E2',
  },
  emergencyPillText: {
    color: '#DC2626',
    fontWeight: '700',
  },
  searchBar: {
    flexDirection: 'row',
    alignItems: 'center',
    marginHorizontal: 16,
    marginBottom: 8,
    paddingHorizontal: 12,
    backgroundColor: '#ffffff',
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#EAECF0',
    height: 44,
  },
  searchIcon: {
    marginRight: 8,
  },
  searchInput: {
    flex: 1,
    fontSize: 14,
    color: '#1A2340',
  },
  sortBar: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 10,
    backgroundColor: '#ffffff',
    borderBottomWidth: 1,
    borderBottomColor: '#EAECF0',
    marginBottom: 8,
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
});
