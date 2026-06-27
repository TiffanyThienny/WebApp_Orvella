import React from 'react';
import {
  TouchableOpacity, Text, StyleSheet, ActivityIndicator,
  ViewStyle, TextStyle,
} from 'react-native';
import { orvellaColors, orvellaRadius, orvellaSpacing, orvellaFontSize } from '../../constants/orvella';

interface ButtonProps {
  title: string;
  onPress: () => void;
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost';
  size?: 'sm' | 'md' | 'lg';
  isLoading?: boolean;
  disabled?: boolean;
  style?: ViewStyle;
}

export function Button({
  title, onPress, variant = 'primary', size = 'md',
  isLoading = false, disabled = false, style,
}: ButtonProps) {
  const isDisabled = disabled || isLoading;
  return (
    <TouchableOpacity
      style={[
        styles.base,
        styles[variant],
        styles[`size_${size}`],
        isDisabled && styles.disabled,
        style,
      ]}
      onPress={onPress}
      activeOpacity={0.75}
      disabled={isDisabled}
    >
      {isLoading ? (
        <ActivityIndicator
          size="small"
          color={variant === 'ghost' ? orvellaColors.primary : '#fff'}
        />
      ) : (
        <Text style={[styles.text, styles[`text_${variant}`], styles[`textSize_${size}`]]}>
          {title}
        </Text>
      )}
    </TouchableOpacity>
  );
}

const styles = StyleSheet.create({
  base: {
    borderRadius: orvellaRadius.md,
    alignItems: 'center',
    justifyContent: 'center',
    flexDirection: 'row',
  },
  primary: { backgroundColor: orvellaColors.primary },
  secondary: { backgroundColor: orvellaColors.accentLight, borderWidth: 1, borderColor: orvellaColors.accent },
  danger: { backgroundColor: orvellaColors.danger },
  ghost: { backgroundColor: 'transparent', borderWidth: 1, borderColor: orvellaColors.primary },
  disabled: { opacity: 0.5 },
  size_sm: { paddingVertical: orvellaSpacing.xs, paddingHorizontal: orvellaSpacing.md, minHeight: 36 },
  size_md: { paddingVertical: orvellaSpacing.sm + 2, paddingHorizontal: orvellaSpacing.lg, minHeight: 48 },
  size_lg: { paddingVertical: orvellaSpacing.md, paddingHorizontal: orvellaSpacing.xl, minHeight: 56 },
  text: { fontWeight: '600' },
  text_primary: { color: '#fff' },
  text_secondary: { color: orvellaColors.accent },
  text_danger: { color: '#fff' },
  text_ghost: { color: orvellaColors.primary },
  textSize_sm: { fontSize: orvellaFontSize.sm },
  textSize_md: { fontSize: orvellaFontSize.md },
  textSize_lg: { fontSize: orvellaFontSize.lg },
});
