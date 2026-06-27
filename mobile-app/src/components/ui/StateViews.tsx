import React from 'react';
import { View, Text, StyleSheet, ActivityIndicator } from 'react-native';
import { orvellaColors, orvellaFontSize, orvellaSpacing } from '../../constants/orvella';

interface LoadingStateProps {
  message?: string;
}

export function LoadingState({ message = 'Loading...' }: LoadingStateProps) {
  return (
    <View style={styles.container}>
      <ActivityIndicator size="large" color={orvellaColors.primary} />
      <Text style={styles.text}>{message}</Text>
    </View>
  );
}

interface EmptyStateProps {
  icon?: string;
  title: string;
  subtitle?: string;
}

export function EmptyState({ icon = '📋', title, subtitle }: EmptyStateProps) {
  return (
    <View style={styles.container}>
      <Text style={styles.icon}>{icon}</Text>
      <Text style={styles.title}>{title}</Text>
      {subtitle && <Text style={styles.subtitle}>{subtitle}</Text>}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: orvellaSpacing.xl,
    gap: orvellaSpacing.sm,
  },
  text: {
    marginTop: orvellaSpacing.sm,
    color: orvellaColors.textSecondary,
    fontSize: orvellaFontSize.md,
  },
  icon: {
    fontSize: 48,
    marginBottom: orvellaSpacing.sm,
  },
  title: {
    fontSize: orvellaFontSize.lg,
    fontWeight: '600',
    color: orvellaColors.textPrimary,
    textAlign: 'center',
  },
  subtitle: {
    fontSize: orvellaFontSize.md,
    color: orvellaColors.textSecondary,
    textAlign: 'center',
    lineHeight: 22,
  },
});
