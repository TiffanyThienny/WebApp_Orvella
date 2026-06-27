import React from 'react';
import { View, StyleSheet, StyleProp, ViewStyle } from 'react-native';
import { orvellaColors, orvellaRadius, orvellaSpacing, orvellaShadow } from '../../constants/orvella';

interface CardProps {
  children: React.ReactNode;
  style?: StyleProp<ViewStyle>;
  variant?: 'default' | 'elevated' | 'outlined';
}

export function Card({ children, style, variant = 'default' }: CardProps) {
  return (
    <View style={[styles.card, styles[variant], style]}>
      {children}
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: orvellaColors.surface,
    borderRadius: orvellaRadius.lg,
    padding: orvellaSpacing.md,
  },
  default: {
    ...orvellaShadow.sm,
  },
  elevated: {
    ...orvellaShadow.md,
  },
  outlined: {
    borderWidth: 1,
    borderColor: orvellaColors.border,
  },
});
