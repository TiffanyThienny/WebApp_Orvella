import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { statusColors, orvellaColors, orvellaRadius, orvellaFontSize } from '../../constants/orvella';

interface StatusBadgeProps {
  status: string;
  size?: 'sm' | 'md';
}

const labelMap: Record<string, string> = {
  normal: 'Normal',
  warning: 'Warning',
  critical: 'Critical',
  pending: 'Pending',
  pending_review: 'Pending Review',
  analyzed: 'Analyzed',
  reviewed: 'Reviewed',
  approved: 'Approved',
  confirmed: 'Confirmed',
  completed: 'Completed',
  cancelled: 'Cancelled',
  rejected: 'Rejected',
  ai_processing: 'Processing',
};

export function StatusBadge({ status, size = 'md' }: StatusBadgeProps) {
  const color = statusColors[status as keyof typeof statusColors] ?? orvellaColors.textMuted;
  const label = labelMap[status] ?? status;
  const bg = color + '20'; // 12% opacity background

  return (
    <View style={[styles.badge, { backgroundColor: bg }, size === 'sm' && styles.badgeSm]}>
      <View style={[styles.dot, { backgroundColor: color }]} />
      <Text style={[styles.text, { color }, size === 'sm' && styles.textSm]} numberOfLines={1}>
        {label}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: orvellaRadius.full,
    gap: 6,
  },
  badgeSm: {
    paddingHorizontal: 8,
    paddingVertical: 2,
  },
  dot: {
    width: 6,
    height: 6,
    borderRadius: 3,
  },
  text: {
    fontSize: orvellaFontSize.sm,
    fontWeight: '600',
  },
  textSm: {
    fontSize: orvellaFontSize.xs,
  },
});
