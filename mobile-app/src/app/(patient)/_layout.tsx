import React from 'react';
import { Tabs } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { StyleSheet } from 'react-native';
import { orvellaColors } from '../../constants/orvella';
import { Ionicons } from '@expo/vector-icons';

import { useAuthStore } from '../../store/auth.store';

export default function PatientLayout() {
  const insets = useSafeAreaInsets();
  return (
    <Tabs
      screenOptions={{
        tabBarActiveTintColor: orvellaColors.primary,
        tabBarInactiveTintColor: orvellaColors.textSecondary,
        tabBarStyle: {
          backgroundColor: orvellaColors.surface,
          borderTopColor: orvellaColors.border,
          height: 60 + insets.bottom,
          paddingBottom: 8 + insets.bottom,
          paddingTop: 8,
        },
        headerShown: false, // Hide headers globally
        headerStyle: {
          backgroundColor: orvellaColors.surface,
        },
        headerTintColor: orvellaColors.textPrimary,
        headerTitleStyle: {
          fontWeight: 'bold',
        },
      }}
    >
      <Tabs.Screen
        name="dashboard"
        options={{
          title: 'Home',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="home-outline" size={size} color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="appointments"
        options={{
          title: 'Book Appointment',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="calendar-outline" size={size} color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="history"
        options={{
          title: 'History',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="time-outline" size={size} color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="scans"
        options={{
          title: 'Scans',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="pulse-outline" size={size} color={color} />
          ),
        }}
      />
      <Tabs.Screen
        name="logout"
        listeners={{
          tabPress: (e) => {
            e.preventDefault();
            useAuthStore.getState().logout();
          },
        }}
        options={{
          href: null, // Hide logout tab from bottom bar
          title: 'Logout',
          tabBarIcon: ({ color, size }) => (
            <Ionicons name="log-out-outline" size={size} color={orvellaColors.danger} />
          ),
          tabBarLabelStyle: { color: orvellaColors.danger },
        }}
      />
    </Tabs>
  );
}
