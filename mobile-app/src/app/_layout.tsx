import { useEffect } from 'react';
import { Stack, router } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { LogBox } from 'react-native';
import { AppProviders } from '../components/providers/AppProviders';

LogBox.ignoreLogs(['Cannot connect to Expo CLI']);
import { useAuthStore } from '../store/auth.store';

function RootNavigation() {
  const { isAuthenticated, isLoading, user, rehydrate } = useAuthStore();

  // Rehydrate auth on cold start
  useEffect(() => {
    rehydrate();
  }, []);

  // Route guard: redirect after auth state resolves
  useEffect(() => {
    if (isLoading) return;

    if (!isAuthenticated) {
      router.replace('/(auth)/login');
      return;
    }

    const role = user?.role_name?.toLowerCase();
    if (role === 'patient') {
      router.replace('/(patient)/dashboard');
    } else if (role === 'medical record') {
      router.replace('/(medrec)/dashboard');
    } else {
      // Admin / Doctor → web-only, show friendly message
      router.replace('/(auth)/login');
    }
  }, [isAuthenticated, isLoading, user]);

  return (
    <>
      {/* @ts-ignore: backgroundColor is valid in some react-native versions but missing in expo-status-bar types */}
      <StatusBar style="light" backgroundColor="#000000" />
      <Stack screenOptions={{ headerShown: false }}>
        <Stack.Screen name="(auth)" />
        <Stack.Screen name="(patient)" />
        <Stack.Screen name="(medrec)" />
      </Stack>
    </>
  );
}

export default function RootLayout() {
  return (
    <AppProviders>
      <RootNavigation />
    </AppProviders>
  );
}
