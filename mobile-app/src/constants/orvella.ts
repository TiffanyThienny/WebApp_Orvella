// Orvella Design Tokens — Healthcare-focused palette
// Use this instead of the default theme.ts for all custom screens

export const orvellaColors = {
  primary: '#1A73E8',
  primaryDark: '#1557B0',
  primaryLight: '#E8F0FE',
  accent: '#00BFA5',
  accentLight: '#E0F7F4',
  success: '#34A853',
  warning: '#F9AB00',
  danger: '#EA4335',
  background: '#F0F4FF',
  surface: '#FFFFFF',
  surfaceVariant: '#F1F5FF',
  border: '#E0E8F5',
  textPrimary: '#1A2340',
  textSecondary: '#5B6E8C',
  textMuted: '#9BA8BB',
  textOnPrimary: '#FFFFFF',
  overlay: 'rgba(26, 35, 64, 0.5)',
};

export const orvellaSpacing = {
  xs: 4, sm: 8, md: 16, lg: 24, xl: 32, xxl: 48,
};

export const orvellaRadius = {
  sm: 8, md: 12, lg: 16, xl: 24, full: 999,
};

export const orvellaFontSize = {
  xs: 11, sm: 13, md: 15, lg: 17, xl: 20, xxl: 24, xxxl: 30,
};

export const orvellaShadow = {
  sm: {
    shadowColor: '#1A2340',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.06,
    shadowRadius: 4,
    elevation: 2,
  },
  md: {
    shadowColor: '#1A2340',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.08,
    shadowRadius: 12,
    elevation: 4,
  },
};

export const statusColors = {
  normal: '#34A853',
  warning: '#F9AB00',
  critical: '#EA4335',
  pending: '#9BA8BB',
  analyzed: '#1A73E8',
  reviewed: '#F9AB00',
  approved: '#34A853',
  confirmed: '#34A853',
  completed: '#1A73E8',
  cancelled: '#EA4335',
  rejected: '#EA4335',
  ai_processing: '#F9AB00',
};
