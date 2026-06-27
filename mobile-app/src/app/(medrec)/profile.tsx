import React from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity } from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '../../store/auth.store';
import { orvellaColors, orvellaSpacing, orvellaRadius, orvellaShadow } from '../../constants/orvella';
import { Button } from '../../components/ui/Button';
import { router } from 'expo-router';

export default function ProfileScreen() {
  const { user, logout } = useAuthStore();

  const handleLogout = () => {
    logout();
  };

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.contentContainer}>
      {/* Profile Header */}
      <View style={styles.profileHeader}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>
            {user?.full_name ? user.full_name.charAt(0).toUpperCase() : 'U'}
          </Text>
        </View>
        <Text style={styles.userName}>{user?.full_name || 'Medical Staff'}</Text>
        <Text style={styles.userRole}>MedRec Administrator</Text>
      </View>

      {/* Account Info Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Account Details</Text>
        
        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>Username</Text>
          <Text style={styles.infoValue}>{user?.username || '—'}</Text>
        </View>

        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>Email Address</Text>
          <Text style={styles.infoValue}>{user?.email || '—'}</Text>
        </View>

        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>Staff Role</Text>
          <Text style={styles.infoValue}>Medical Record (MedRec)</Text>
        </View>
      </View>

      {/* Actions Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Quick Links</Text>

        <TouchableOpacity 
          style={styles.menuItem} 
          onPress={() => router.push('/(medrec)/notifications')}
          activeOpacity={0.8}
        >
          <View style={[styles.menuIcon, { backgroundColor: '#F0FDFA' }]}>
            <Ionicons name="notifications-outline" size={18} color={orvellaColors.accent} />
          </View>
          <Text style={styles.menuText}>Notifications & Alert Queue</Text>
          <Ionicons name="chevron-forward" size={16} color={orvellaColors.textMuted} />
        </TouchableOpacity>
      </View>

      {/* App Info Section */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Application Information</Text>
        
        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>App Version</Text>
          <Text style={styles.infoValue}>1.0.0 (Production)</Text>
        </View>

        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>Server Connection</Text>
          <Text style={[styles.infoValue, { color: '#059669', fontWeight: 'bold' }]}>Online / Secure</Text>
        </View>
      </View>

      {/* Logout Button */}
      <Button 
        title="Logout" 
        onPress={handleLogout} 
        style={styles.logoutBtn} 
      />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#F4F6FA',
  },
  contentContainer: {
    padding: orvellaSpacing.md,
    paddingBottom: orvellaSpacing.xxl,
  },
  profileHeader: {
    alignItems: 'center',
    backgroundColor: '#ffffff',
    borderRadius: orvellaRadius.md,
    padding: orvellaSpacing.lg,
    marginBottom: orvellaSpacing.lg,
    ...orvellaShadow.sm,
  },
  avatar: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: orvellaColors.primaryLight,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 12,
  },
  avatarText: {
    fontSize: 24,
    fontWeight: 'bold',
    color: orvellaColors.primary,
  },
  userName: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#1A2340',
    marginBottom: 4,
  },
  userRole: {
    fontSize: 13,
    color: orvellaColors.textSecondary,
  },
  section: {
    backgroundColor: '#ffffff',
    borderRadius: orvellaRadius.md,
    padding: orvellaSpacing.md,
    marginBottom: orvellaSpacing.lg,
    ...orvellaShadow.sm,
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#1A2340',
    marginBottom: 12,
    paddingHorizontal: 4,
  },
  menuItem: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 12,
    paddingHorizontal: 4,
    borderBottomWidth: 1,
    borderBottomColor: '#F3F4F6',
  },
  menuIcon: {
    width: 32,
    height: 32,
    borderRadius: 8,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
  },
  menuText: {
    flex: 1,
    fontSize: 14,
    color: '#374151',
  },
  logoutBtn: {
    backgroundColor: orvellaColors.danger,
    marginTop: orvellaSpacing.sm,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#F3F4F6',
    paddingHorizontal: 4,
  },
  infoLabel: {
    fontSize: 13,
    color: orvellaColors.textSecondary,
    fontWeight: '500',
  },
  infoValue: {
    fontSize: 13,
    color: '#1A2340',
    fontWeight: '700',
  },
});
