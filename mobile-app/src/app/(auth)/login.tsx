import React, { useState, useEffect, useRef } from 'react';
import {
  View, Text, TextInput, StyleSheet, ScrollView,
  TouchableOpacity, KeyboardAvoidingView, Platform, Alert,
  Keyboard, StatusBar,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';
import { useAuthStore } from '../../store/auth.store';
import { Button } from '../../components/ui/Button';
import {
  orvellaColors, orvellaSpacing, orvellaRadius,
  orvellaFontSize, orvellaShadow,
} from '../../constants/orvella';

export default function LoginScreen() {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [isKeyboardVisible, setKeyboardVisible] = useState(false);
  const [focusedField, setFocusedField] = useState<string | null>(null);
  const { login, isLoading } = useAuthStore();

  const usernameRef = useRef<TextInput>(null);
  const passwordRef = useRef<TextInput>(null);

  useEffect(() => {
    const showEvent = Platform.OS === 'ios' ? 'keyboardWillShow' : 'keyboardDidShow';
    const hideEvent = Platform.OS === 'ios' ? 'keyboardWillHide' : 'keyboardDidHide';
    const showListener = Keyboard.addListener(showEvent, () => setKeyboardVisible(true));
    const hideListener = Keyboard.addListener(hideEvent, () => setKeyboardVisible(false));
    return () => {
      showListener.remove();
      hideListener.remove();
    };
  }, []);

  const handleLogin = async () => {
    if (!username.trim() || !password.trim()) {
      Alert.alert('Attention', 'Username and password cannot be empty.');
      return;
    }
    try {
      await login(username.trim(), password);
    } catch (err: any) {
      const msg =
        err?.response?.data?.error ??
        err?.response?.data?.message ??
        err?.message ??
        'Login failed. Please check your username and password.';
      Alert.alert('Login Failed', msg);
    }
  };

  return (
    <KeyboardAvoidingView
      style={styles.root}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      keyboardVerticalOffset={Platform.OS === 'ios' ? 64 : 0}
    >
      <StatusBar barStyle="dark-content" backgroundColor={styles.root.backgroundColor} />
      <ScrollView
        contentContainerStyle={[
          styles.scroll,
          isKeyboardVisible && { justifyContent: 'flex-start', paddingTop: 40 }
        ]}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
      >
        {/* Logo / Brand */}
        {!isKeyboardVisible && (
          <View style={styles.brandSection}>
            <View style={styles.logoContainer}>
              <Ionicons name="medical" size={24} color="#ffffff" />
            </View>
            <Text style={styles.brandName}>Orvella</Text>
            <Text style={styles.brandTagline}>Integrated Healthcare Management System</Text>
          </View>
        )}

        {/* Login Card */}
        <View style={styles.card}>
          <View style={styles.cardHeader}>
            <Text style={styles.cardTitle}>Sign In to Account</Text>
            <Text style={styles.cardSubtitle}>Please enter your credentials to continue</Text>
          </View>

          {/* Divider */}
          <View style={styles.divider} />

          {/* Username Field */}
          <View style={styles.fieldGroup}>
            <Text style={styles.label}>Username</Text>
            <TouchableOpacity 
              activeOpacity={1}
              onPress={() => usernameRef.current?.focus()}
              style={[
                styles.inputWrapper,
                focusedField === 'username' && styles.inputWrapperFocused
              ]}
            >
              <Ionicons
                name="person-outline"
                size={18}
                color={focusedField === 'username' ? orvellaColors.primary : orvellaColors.textMuted}
                style={styles.inputIcon}
              />
              <TextInput
                ref={usernameRef}
                style={styles.input}
                placeholder="Enter your username"
                placeholderTextColor={orvellaColors.textMuted}
                value={username}
                onChangeText={setUsername}
                autoCapitalize="none"
                autoCorrect={false}
                returnKeyType="next"
                onFocus={() => setFocusedField('username')}
                onBlur={() => setFocusedField(null)}
              />
            </TouchableOpacity>
          </View>
 
          {/* Password Field */}
          <View style={styles.fieldGroup}>
            <Text style={styles.label}>Password</Text>
            <TouchableOpacity 
              activeOpacity={1}
              onPress={() => passwordRef.current?.focus()}
              style={[
                styles.inputWrapper,
                focusedField === 'password' && styles.inputWrapperFocused
              ]}
            >
              <Ionicons
                name="lock-closed-outline"
                size={18}
                color={focusedField === 'password' ? orvellaColors.primary : orvellaColors.textMuted}
                style={styles.inputIcon}
              />
              <TextInput
                ref={passwordRef}
                style={[styles.input, styles.inputPassword]}
                placeholder="Enter your password"
                placeholderTextColor={orvellaColors.textMuted}
                value={password}
                onChangeText={setPassword}
                secureTextEntry={!showPassword}
                returnKeyType="done"
                onSubmitEditing={handleLogin}
                onFocus={() => setFocusedField('password')}
                onBlur={() => setFocusedField(null)}
              />
              <TouchableOpacity
                onPress={() => setShowPassword((v) => !v)}
                style={styles.eyeBtn}
                hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
              >
                <Ionicons
                  name={showPassword ? 'eye-off-outline' : 'eye-outline'}
                  size={20}
                  color={orvellaColors.textSecondary}
                />
              </TouchableOpacity>
            </TouchableOpacity>
          </View>

          {/* Login Button */}
          <TouchableOpacity
            style={styles.loginBtn}
            onPress={handleLogin}
            activeOpacity={0.85}
          >
            {isLoading ? (
              <Text style={styles.loginBtnText}>Processing...</Text>
            ) : (
              <>
                <Ionicons name="log-in-outline" size={18} color="#ffffff" style={{ marginRight: 8 }} />
                <Text style={styles.loginBtnText}>Sign In</Text>
              </>
            )}
          </TouchableOpacity>

          {/* Info note */}
          <View style={styles.noteBox}>
            <Ionicons name="information-circle-outline" size={14} color="#6B7280" />
            <Text style={styles.noteText}>
              Admin and Doctor can only access through the web portal.
            </Text>
          </View>
        </View>

        {/* Footer */}
        <Text style={styles.footer}>
          Orvella © {new Date().getFullYear()} · Integrated Healthcare System
        </Text>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    backgroundColor: '#F4F6FA',
  },
  scroll: {
    flexGrow: 1,
    justifyContent: 'center',
    paddingHorizontal: orvellaSpacing.lg,
    paddingVertical: 20,
  },
  brandSection: {
    alignItems: 'center',
    marginBottom: 24,
  },
  logoContainer: {
    width: 52,
    height: 52,
    borderRadius: 14,
    backgroundColor: orvellaColors.primary,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 10,
    ...orvellaShadow.sm,
  },
  brandName: {
    fontSize: 24,
    fontWeight: '700',
    color: '#1A2340',
    letterSpacing: 0.3,
    marginBottom: 2,
  },
  brandTagline: {
    fontSize: 12,
    color: orvellaColors.textSecondary,
    textAlign: 'center',
  },
  card: {
    backgroundColor: '#ffffff',
    borderRadius: 16,
    padding: 20,
    ...orvellaShadow.sm,
    marginBottom: 16,
  },
  cardHeader: {
    marginBottom: 12,
  },
  cardTitle: {
    fontSize: 18,
    fontWeight: '700',
    color: '#1A2340',
    marginBottom: 2,
  },
  cardSubtitle: {
    fontSize: 12,
    color: orvellaColors.textSecondary,
    lineHeight: 16,
  },
  divider: {
    height: 1,
    backgroundColor: '#EAECF0',
    marginBottom: 16,
  },
  fieldGroup: {
    marginBottom: 14,
  },
  label: {
    fontSize: 12,
    fontWeight: '600',
    color: '#374151',
    marginBottom: 6,
  },
  inputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#D1D5DB',
    borderRadius: 10,
    backgroundColor: '#F9FAFB',
    paddingHorizontal: 12,
    height: 46,
  },
  inputWrapperFocused: {
    borderColor: orvellaColors.primary,
    backgroundColor: '#ffffff',
    shadowColor: orvellaColors.primary,
    shadowOffset: { width: 0, height: 0 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
    elevation: 2,
  },
  inputIcon: {
    marginRight: 8,
  },
  input: {
    flex: 1,
    fontSize: 14,
    color: '#1A2340',
  },
  inputPassword: {
    paddingRight: 8,
  },
  eyeBtn: {
    padding: 4,
  },
  loginBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: orvellaColors.primary,
    borderRadius: 10,
    height: 46,
    marginTop: 4,
    ...orvellaShadow.sm,
  },
  loginBtnDisabled: {
    opacity: 0.7,
  },
  loginBtnText: {
    color: '#ffffff',
    fontSize: 15,
    fontWeight: '600',
    letterSpacing: 0.2,
  },
  noteBox: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 8,
    marginTop: 12,
    padding: 10,
    backgroundColor: '#F3F4F6',
    borderRadius: 8,
  },
  noteText: {
    flex: 1,
    fontSize: 11,
    color: '#6B7280',
    lineHeight: 16,
  },
  footer: {
    textAlign: 'center',
    fontSize: 11,
    color: orvellaColors.textMuted,
    marginTop: 8,
  },
});
