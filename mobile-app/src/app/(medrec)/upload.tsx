import React, { useState, useEffect } from 'react';
import {
  ScrollView,
  StyleSheet,
  Text,
  View,
  TextInput,
  TouchableOpacity,
  Alert,
  Image,
  ActivityIndicator,
  Platform,
  Modal,
  RefreshControl,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { Picker } from '@react-native-picker/picker';
import * as ImagePicker from 'expo-image-picker';
import { Ionicons } from '@expo/vector-icons';
import { router, useLocalSearchParams } from 'expo-router';

import { usePatients, useDoctors, useUploadScanWorkflow } from '../../hooks/useApi';
import { useAuthStore } from '../../store/auth.store';
import { orvellaColors, orvellaFontSize, orvellaSpacing, orvellaRadius, orvellaShadow } from '../../constants/orvella';
import { Card } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';

// ─── Validation Helpers ────────────────────────────────────────────────────────
function validateSystolic(v: string) {
  if (!v.trim()) return 'Systolic is required.';
  const n = Number(v);
  if (isNaN(n) || n < 50 || n > 300) return 'Must be between 50–300 mmHg.';
  return '';
}
function validateDiastolic(v: string, sys: string) {
  if (!v.trim()) return 'Diastolic is required.';
  const n = Number(v);
  if (isNaN(n) || n < 30 || n > 200) return 'Must be between 30–200 mmHg.';
  if (Number(sys) > 0 && n >= Number(sys)) return 'Must be lower than systolic.';
  return '';
}
function validateHeartRate(v: string) {
  if (!v.trim()) return 'Heart rate is required.';
  const n = Number(v);
  if (isNaN(n) || n < 30 || n > 250) return 'Must be between 30–250 bpm.';
  return '';
}
function validateWeight(v: string) {
  if (!v.trim()) return 'Weight is required.';
  const n = Number(v);
  if (isNaN(n) || n < 1 || n > 500) return 'Must be between 1–500 kg.';
  return '';
}
function validateOxygen(v: string) {
  if (!v.trim()) return 'SpO2 is required.';
  const n = Number(v);
  if (isNaN(n) || n < 50 || n > 100) return 'Must be between 50–100%.';
  return '';
}
function validateTemperature(v: string) {
  if (!v.trim()) return 'Temperature is required.';
  const n = Number(v);
  if (isNaN(n) || n < 30 || n > 45) return 'Must be between 30–45°C.';
  return '';
}
function validateNotes(v: string) {
  if (!v.trim()) return 'Clinical notes are required.';
  if (v.trim().length < 10) return 'Must be at least 10 characters.';
  return '';
}

// ─── Inline Error Component ────────────────────────────────────────────────────
function FieldError({ error }: { error: string }) {
  if (!error) return null;
  return (
    <View style={errStyles.container}>
      <Ionicons name="alert-circle" size={12} color={orvellaColors.danger} />
      <Text style={errStyles.text}>{error}</Text>
    </View>
  );
}
const errStyles = StyleSheet.create({
  container: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: 3,
    marginBottom: 4,
  },
  text: {
    fontSize: 11,
    color: orvellaColors.danger,
    fontWeight: '500',
  },
});

export default function MedrecUploadScreen() {
  const params = useLocalSearchParams();
  const { user } = useAuthStore();
  const { data: patients, isLoading: loadingPatients, refetch: refetchPatients } = usePatients();
  const { data: doctors, isLoading: loadingDoctors, refetch: refetchDoctors } = useDoctors();
  const [refreshing, setRefreshing] = useState(false);

  const onRefresh = React.useCallback(async () => {
    setRefreshing(true);
    await Promise.all([refetchPatients(), refetchDoctors()]);
    setRefreshing(false);
  }, [refetchPatients, refetchDoctors]);
  const uploadWorkflow = useUploadScanWorkflow();
  const insets = useSafeAreaInsets();

  // Form states
  const [patientId, setPatientId] = useState<string>(params.patientId ? params.patientId.toString() : '');
  const [doctorId, setDoctorId] = useState<string>(params.doctorId ? params.doctorId.toString() : '');
  const [systolic, setSystolic] = useState<string>('');
  const [diastolic, setDiastolic] = useState<string>('');
  const [heartRate, setHeartRate] = useState<string>('');
  const [weight, setWeight] = useState<string>('');
  const [oxygenLevel, setOxygenLevel] = useState<string>('');
  const [temperature, setTemperature] = useState<string>('');
  const [notes, setNotes] = useState<string>('');
  const [imageUri, setImageUri] = useState<string | null>(null);
  const [isPickingImage, setIsPickingImage] = useState(false);


  // Real-time inline validation errors
  const [errors, setErrors] = useState<Record<string, string>>({});
  // Track which fields have been "touched" (blurred at least once)
  const [touched, setTouched] = useState<Record<string, boolean>>({});

  const touch = (field: string) => setTouched((prev) => ({ ...prev, [field]: true }));

  const setError = (field: string, msg: string) =>
    setErrors((prev) => ({ ...prev, [field]: msg }));

  useEffect(() => {
    const timer = setTimeout(() => {
      if (params.patientId) setPatientId(params.patientId.toString());
      if (params.doctorId) setDoctorId(params.doctorId.toString());
      if (params.systolic) setSystolic(params.systolic.toString());
      if (params.diastolic) setDiastolic(params.diastolic.toString());
      if (params.heartRate) setHeartRate(params.heartRate.toString());
      if (params.weight) setWeight(params.weight.toString());
      if (params.oxygenLevel) setOxygenLevel(params.oxygenLevel.toString());
      if (params.temperature) setTemperature(params.temperature.toString());
      if (params.notes) setNotes(params.notes.toString());
    }, 0);
    return () => clearTimeout(timer);
  }, [params.patientId, params.doctorId, params.systolic, params.diastolic, params.heartRate, params.weight, params.oxygenLevel, params.temperature, params.notes]);


  const [step, setStep] = useState<number>(1);
  const [uploadStepName, setUploadStepName] = useState<string>('');

  // Custom Modal States
  const [alertVisible, setAlertVisible] = useState(false);
  const [alertTitle, setAlertTitle] = useState('');
  const [alertMessage, setAlertMessage] = useState('');

  const showCustomAlert = (title: string, message: string) => {
    setAlertTitle(title);
    setAlertMessage(message);
    setAlertVisible(true);
  };

  const pickImage = async () => {
    const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (status !== 'granted') {
      Alert.alert('Permission Denied', 'The app requires gallery permissions to select a CT Scan image.');
      return;
    }
    setIsPickingImage(true);
    try {
      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: 'images',
        allowsEditing: false,
        quality: 1,
      });
      if (!result.canceled && result.assets && result.assets.length > 0) {
        setImageUri(result.assets[0].uri);
        setError('image', '');
      }
    } finally {
      // Delay resetting to prevent tap pass-through (ghost clicks) on underlying buttons
      setTimeout(() => {
        setIsPickingImage(false);
      }, 600);
    }
  };

  const handleNextStep = () => {
    if (!patientId) {
      showCustomAlert('Validation Error', 'Please select a patient first.');
      return;
    }
    if (!doctorId) {
      showCustomAlert('Validation Error', 'Please select a specialist/doctor first.');
      return;
    }
    setStep(2);
  };

  const handleSubmit = async () => {
    // Prevent auto-submission caused by tap pass-through from the ImagePicker cropper
    if (isPickingImage) return;

    // Force-touch all fields so all errors show
    const allTouched: Record<string, boolean> = {
      systolic: true, diastolic: true, heartRate: true,
      weight: true, oxygenLevel: true, temperature: true,
      notes: true, image: true,
    };
    setTouched(allTouched);

    const e: Record<string, string> = {
      systolic: validateSystolic(systolic),
      diastolic: validateDiastolic(diastolic, systolic),
      heartRate: validateHeartRate(heartRate),
      weight: validateWeight(weight),
      oxygenLevel: validateOxygen(oxygenLevel),
      temperature: validateTemperature(temperature),
      notes: validateNotes(notes),
      image: imageUri ? '' : 'Please select a CT Scan image.',
    };
    setErrors(e);

    const hasErrors = Object.values(e).some((v) => !!v);
    if (hasErrors) return;

    const filename = imageUri!.split('/').pop() || 'scan.jpg';
    const match = /\.(\w+)$/.exec(filename);
    const ext = match ? match[1].toLowerCase() : 'jpg';
    if (ext !== 'jpg' && ext !== 'jpeg' && ext !== 'png') {
      Alert.alert('File Validation', 'Unsupported file format. Only JPG, JPEG, or PNG formats are allowed.');
      return;
    }

    try {
      setUploadStepName('Uploading CT Scan image...');
      const formData = new FormData();
      formData.append('patient_id', patientId);

      if (Platform.OS === 'web') {
        const response = await fetch(imageUri!);
        const blob = await response.blob();
        formData.append('image', blob, filename);
      } else {
        const type = match ? `image/${match[1]}` : `image/jpeg`;
        formData.append('image', { uri: imageUri!, name: filename, type } as any);
      }

      setUploadStepName('Saving medical records...');
      await uploadWorkflow.mutateAsync({
        patientId: Number(patientId),
        doctorId: Number(doctorId),
        imageFormData: formData,
        vitals: {
          systolic: Number(systolic),
          diastolic: Number(diastolic),
          heart_rate: Number(heartRate),
          weight: Number(weight),
          oxygen_level: Number(oxygenLevel),
          temperature: Number(temperature),
          notes,
        },
      });

      Alert.alert('Success', 'CT Scan successfully uploaded and vital signs saved.');
      setPatientId(''); setDoctorId('');
      setSystolic(''); setDiastolic(''); setHeartRate('');
      setWeight(''); setOxygenLevel(''); setTemperature('');
      setNotes(''); setImageUri(null);
      setErrors({}); setTouched({});
      setStep(1);
      router.push('/(medrec)/scans');
    } catch (error: any) {
      console.error(error);
      Alert.alert('Upload Failed', error.message || 'An error occurred while uploading the medical records.');
    } finally {
      setUploadStepName('');
    }
  };

  if (loadingPatients || loadingDoctors) {
    return (
      <View style={styles.loadingContainer}>
        <ActivityIndicator size="large" color={orvellaColors.primary} />
        <Text style={styles.loadingText}>Loading references...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Top App Bar */}
      <View style={[styles.topAppBar, { paddingTop: Math.max(insets.top, 16) }]}>
        <View style={styles.appBarLeft}>
          <Text style={styles.appBarTitle}>Upload Scan</Text>
        </View>
        <View style={styles.appBarRight}>
          <View style={styles.badge}>
            <Ionicons name="shield-checkmark" size={10} color={orvellaColors.primary} />
            <Text style={styles.badgeText}>HIPAA</Text>
          </View>
          <View style={styles.avatarMini}>
            <Text style={styles.avatarMiniText}>{user?.full_name ? user.full_name.charAt(0).toUpperCase() : 'U'}</Text>
          </View>
        </View>
      </View>

      <ScrollView 
        style={styles.scrollContainer} 
        contentContainerStyle={styles.contentContainer}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[orvellaColors.primary]} />
        }
      >
        {/* Step Indicator */}
        <View style={styles.stepIndicatorContainer}>
          <View style={[styles.stepDot, step >= 1 && styles.stepDotActive]}>
            {step > 1 ? (
              <Ionicons name="checkmark" size={12} color="#fff" />
            ) : (
              <Text style={[styles.stepDotText, step >= 1 && styles.stepDotTextActive]}>1</Text>
            )}
          </View>
          <Text style={[styles.stepLabel, step >= 1 && styles.stepLabelActive]}>Assignment</Text>
          
          <View style={[styles.stepLine, step >= 2 && styles.stepLineActive]} />
          
          <View style={[styles.stepDot, step >= 2 && styles.stepDotActive]}>
            <Text style={[styles.stepDotText, step >= 2 && styles.stepDotTextActive]}>2</Text>
          </View>
          <Text style={[styles.stepLabel, step >= 2 && styles.stepLabelActive]}>Vitals & Scan</Text>
        </View>

      {step === 1 ? (
        <Card style={styles.formCard}>
          <Text style={styles.sectionTitle}>1. Register Patient for Scan</Text>
          
          <View style={styles.infoBox}>
            <Ionicons name="medical" size={20} color={orvellaColors.primary} />
            <Text style={styles.infoBoxText}>
              Select a patient profile and assign a specialist doctor to begin the cervical scan analysis.
            </Text>
          </View>

          <View style={styles.labelRow}>
            <Ionicons name="person-circle-outline" size={16} color={orvellaColors.textSecondary} />
            <Text style={styles.inputLabel}>
              Select Patient <Text style={{ color: orvellaColors.danger }}>*</Text>
            </Text>
          </View>
          <View style={[styles.pickerContainer, !patientId && touched.patientId ? styles.pickerError : {}]}>
            <Picker
              selectedValue={patientId}
              onValueChange={(v) => { setPatientId(v); touch('patientId'); }}
              style={styles.picker}
            >
              <Picker.Item label="— Select Patient —" value="" />
              {patients?.map((p: any) => (
                <Picker.Item
                  key={p.id}
                  label={`${p.name} (ID: ${p.id})`}
                  value={p.id.toString()}
                />
              ))}
            </Picker>
          </View>

          <View style={styles.labelRow}>
            <Ionicons name="medkit-outline" size={16} color={orvellaColors.textSecondary} />
            <Text style={styles.inputLabel}>
              Select Specialist Doctor <Text style={{ color: orvellaColors.danger }}>*</Text>
            </Text>
          </View>
          <View style={[styles.pickerContainer, !doctorId && touched.doctorId ? styles.pickerError : {}]}>
            <Picker
              selectedValue={doctorId}
              onValueChange={(v) => { setDoctorId(v); touch('doctorId'); }}
              style={styles.picker}
            >
              <Picker.Item label="— Select Doctor —" value="" />
              {doctors?.map((d: any) => (
                <Picker.Item
                  key={d.id}
                  label={`Dr. ${d.full_name} (${d.specialty || 'Specialist'})`}
                  value={d.id.toString()}
                />
              ))}
            </Picker>
          </View>

          <Button style={styles.actionButton} title="Continue →" onPress={handleNextStep} />
        </Card>
      ) : (
        <View style={{ gap: orvellaSpacing.md }}>
          {/* Vitals Card */}
          <Card style={styles.formCard}>
            <Text style={styles.sectionTitle}>2. Baseline Vital Signs</Text>

            <View style={styles.gridRow}>
              <View style={styles.gridCol}>
                <Text style={styles.inputLabel}>Systolic (mmHg) <Text style={{ color: orvellaColors.danger }}>*</Text></Text>
                <TextInput
                  keyboardType="numeric"
                  placeholder="e.g. 120"
                  placeholderTextColor={orvellaColors.textMuted}
                  style={[styles.input, touched.systolic && errors.systolic ? styles.inputError : {}]}
                  value={systolic}
                  onChangeText={(v) => {
                    setSystolic(v);
                    if (touched.systolic) setError('systolic', validateSystolic(v));
                    if (touched.diastolic) setError('diastolic', validateDiastolic(diastolic, v));
                  }}
                  onBlur={() => { touch('systolic'); setError('systolic', validateSystolic(systolic)); }}
                />
                {touched.systolic && <FieldError error={errors.systolic} />}
              </View>

              <View style={styles.gridCol}>
                <Text style={styles.inputLabel}>Diastolic (mmHg) <Text style={{ color: orvellaColors.danger }}>*</Text></Text>
                <TextInput
                  keyboardType="numeric"
                  placeholder="e.g. 80"
                  placeholderTextColor={orvellaColors.textMuted}
                  style={[styles.input, touched.diastolic && errors.diastolic ? styles.inputError : {}]}
                  value={diastolic}
                  onChangeText={(v) => {
                    setDiastolic(v);
                    if (touched.diastolic) setError('diastolic', validateDiastolic(v, systolic));
                  }}
                  onBlur={() => { touch('diastolic'); setError('diastolic', validateDiastolic(diastolic, systolic)); }}
                />
                {touched.diastolic && <FieldError error={errors.diastolic} />}
              </View>
            </View>

            <View style={styles.gridRow}>
              <View style={styles.gridCol}>
                <Text style={styles.inputLabel}>Heart Rate (bpm) <Text style={{ color: orvellaColors.danger }}>*</Text></Text>
                <TextInput
                  keyboardType="numeric"
                  placeholder="e.g. 72"
                  placeholderTextColor={orvellaColors.textMuted}
                  style={[styles.input, touched.heartRate && errors.heartRate ? styles.inputError : {}]}
                  value={heartRate}
                  onChangeText={(v) => {
                    setHeartRate(v);
                    if (touched.heartRate) setError('heartRate', validateHeartRate(v));
                  }}
                  onBlur={() => { touch('heartRate'); setError('heartRate', validateHeartRate(heartRate)); }}
                />
                {touched.heartRate && <FieldError error={errors.heartRate} />}
              </View>

              <View style={styles.gridCol}>
                <Text style={styles.inputLabel}>Weight (kg) <Text style={{ color: orvellaColors.danger }}>*</Text></Text>
                <TextInput
                  keyboardType="numeric"
                  placeholder="e.g. 68.5"
                  placeholderTextColor={orvellaColors.textMuted}
                  style={[styles.input, touched.weight && errors.weight ? styles.inputError : {}]}
                  value={weight}
                  onChangeText={(v) => {
                    setWeight(v);
                    if (touched.weight) setError('weight', validateWeight(v));
                  }}
                  onBlur={() => { touch('weight'); setError('weight', validateWeight(weight)); }}
                />
                {touched.weight && <FieldError error={errors.weight} />}
              </View>
            </View>

            <View style={styles.gridRow}>
              <View style={styles.gridCol}>
                <Text style={styles.inputLabel}>SpO2 / Oxygen (%) <Text style={{ color: orvellaColors.danger }}>*</Text></Text>
                <TextInput
                  keyboardType="numeric"
                  placeholder="e.g. 98"
                  placeholderTextColor={orvellaColors.textMuted}
                  style={[styles.input, touched.oxygenLevel && errors.oxygenLevel ? styles.inputError : {}]}
                  value={oxygenLevel}
                  onChangeText={(v) => {
                    setOxygenLevel(v);
                    if (touched.oxygenLevel) setError('oxygenLevel', validateOxygen(v));
                  }}
                  onBlur={() => { touch('oxygenLevel'); setError('oxygenLevel', validateOxygen(oxygenLevel)); }}
                />
                {touched.oxygenLevel && <FieldError error={errors.oxygenLevel} />}
              </View>

              <View style={styles.gridCol}>
                <Text style={styles.inputLabel}>Temperature (°C) <Text style={{ color: orvellaColors.danger }}>*</Text></Text>
                <TextInput
                  keyboardType="numeric"
                  placeholder="e.g. 36.5"
                  placeholderTextColor={orvellaColors.textMuted}
                  style={[styles.input, touched.temperature && errors.temperature ? styles.inputError : {}]}
                  value={temperature}
                  onChangeText={(v) => {
                    setTemperature(v);
                    if (touched.temperature) setError('temperature', validateTemperature(v));
                  }}
                  onBlur={() => { touch('temperature'); setError('temperature', validateTemperature(temperature)); }}
                />
                {touched.temperature && <FieldError error={errors.temperature} />}
              </View>
            </View>

            <Text style={styles.inputLabel}>
              Initial Clinical Notes <Text style={{ color: orvellaColors.danger }}>*</Text>
            </Text>
            <TextInput
              multiline
              numberOfLines={4}
              placeholder="Enter initial symptoms or primary complaints (min. 10 characters)..."
              placeholderTextColor={orvellaColors.textMuted}
              style={[
                styles.input, styles.textArea,
                touched.notes && errors.notes ? styles.inputError : {},
              ]}
              value={notes}
              onChangeText={(v) => {
                setNotes(v);
                if (touched.notes) setError('notes', validateNotes(v));
              }}
              onBlur={() => { touch('notes'); setError('notes', validateNotes(notes)); }}
            />
            {touched.notes && <FieldError error={errors.notes} />}
            <Text style={styles.charCount}>{notes.trim().length} / 10+ characters required</Text>
          </Card>

          {/* Cervical Scan Upload Card */}
          <Card style={styles.formCard}>
            <Text style={styles.sectionTitle}>3. Cervical Medical Image</Text>

            {imageUri ? (
              <View style={styles.imagePreviewContainer}>
                <Image source={{ uri: imageUri }} style={styles.imagePreview} />
                <TouchableOpacity style={styles.removeImageButton} onPress={() => { setImageUri(null); setError('image', 'Please select a CT Scan image.'); }}>
                  <Ionicons name="trash-outline" size={16} color="#ffffff" />
                  <Text style={styles.removeImageText}>Remove</Text>
                </TouchableOpacity>
              </View>
            ) : (
              <TouchableOpacity
                style={[styles.imagePickerPlaceholder, touched.image && errors.image ? styles.imagePickerError : {}]}
                onPress={pickImage}
              >
                <View style={styles.uploadIconBg}>
                  <Ionicons name="cloud-upload-outline" size={32} color={orvellaColors.primary} />
                </View>
                <Text style={styles.pickerText}>Tap to Select CT Scan Image</Text>
                <Text style={styles.pickerHint}>JPEG or PNG format</Text>
              </TouchableOpacity>
            )}
            {touched.image && <FieldError error={errors.image} />}

            {uploadWorkflow.isPending && (
              <View style={styles.progressContainer}>
                <ActivityIndicator size="small" color={orvellaColors.primary} />
                <Text style={styles.progressText}>{uploadStepName}</Text>
              </View>
            )}

            <View style={styles.buttonRow}>
              <Button
                variant="ghost"
                title="← Back"
                size="sm"
                style={styles.halfButton}
                onPress={() => setStep(1)}
                disabled={uploadWorkflow.isPending}
              />
              <Button
                variant="primary"
                title={uploadWorkflow.isPending ? 'Uploading...' : 'Submit Scan'}
                size="sm"
                style={styles.halfButton}
                onPress={handleSubmit}
                isLoading={uploadWorkflow.isPending}
              />
            </View>
          </Card>
        </View>
      )}

      {/* Custom Alert Modal */}
      <Modal
        animationType="fade"
        transparent={true}
        visible={alertVisible}
        onRequestClose={() => setAlertVisible(false)}
      >
        <View style={styles.modalOverlay}>
          <View style={styles.modalContainer}>
            <View style={styles.modalIconContainer}>
              <Ionicons name="warning" size={36} color={orvellaColors.danger} />
            </View>
            <Text style={styles.modalTitle}>{alertTitle}</Text>
            <Text style={styles.modalMessage}>{alertMessage}</Text>
            <TouchableOpacity
              style={styles.modalButton}
              onPress={() => setAlertVisible(false)}
            >
              <Text style={styles.modalButtonText}>OK, I Understand</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
      </ScrollView>
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
    backgroundColor: '#ffffff',
    paddingHorizontal: orvellaSpacing.md,
    paddingBottom: orvellaSpacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: '#EAECF0',
    zIndex: 10,
  },
  appBarLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  appBarTitle: {
    fontSize: 18,
    fontWeight: '800',
    color: '#1A2340',
  },
  appBarRight: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
  avatarMini: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: orvellaColors.primaryLight,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1.5,
    borderColor: '#ffffff',
    ...orvellaShadow.sm,
  },
  avatarMiniText: {
    fontSize: 13,
    fontWeight: '700',
    color: orvellaColors.primary,
  },
  scrollContainer: {
    flex: 1,
  },
  contentContainer: {
    padding: orvellaSpacing.md,
    paddingBottom: orvellaSpacing.xxl,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: orvellaColors.background,
  },
  loadingText: {
    marginTop: orvellaSpacing.sm,
    color: orvellaColors.textSecondary,
    fontSize: orvellaFontSize.sm,
  },
  badge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: orvellaColors.primaryLight,
    paddingVertical: 4,
    paddingHorizontal: 8,
    borderRadius: 12,
  },
  badgeText: {
    fontSize: 10,
    fontWeight: '700',
    color: orvellaColors.primary,
  },
  stepIndicatorContainer: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    marginVertical: orvellaSpacing.sm,
    marginBottom: orvellaSpacing.lg,
  },
  stepDot: {
    width: 24,
    height: 24,
    borderRadius: 12,
    backgroundColor: '#EEF2F6',
    justifyContent: 'center',
    alignItems: 'center',
  },
  stepDotActive: {
    backgroundColor: orvellaColors.primary,
  },
  stepDotText: {
    color: '#94A3B8',
    fontSize: 12,
    fontWeight: '700',
  },
  stepDotTextActive: {
    color: '#ffffff',
  },
  stepLine: {
    width: 40,
    height: 2,
    backgroundColor: '#EEF2F6',
    marginHorizontal: 8,
  },
  stepLineActive: {
    backgroundColor: orvellaColors.primary,
  },
  stepLabel: {
    fontSize: 12,
    color: '#94A3B8',
    fontWeight: '600',
    marginLeft: 6,
  },
  stepLabelActive: {
    color: orvellaColors.primary,
    fontWeight: '700',
  },
  formCard: {
    backgroundColor: '#ffffff',
    borderRadius: 16,
    borderWidth: 1,
    borderColor: '#EAECF0',
    padding: orvellaSpacing.md,
    gap: orvellaSpacing.xs,
    ...orvellaShadow.sm,
  },
  sectionTitle: {
    fontSize: orvellaFontSize.md,
    fontWeight: 'bold',
    color: orvellaColors.textPrimary,
    marginBottom: orvellaSpacing.sm,
    borderBottomWidth: 1,
    borderBottomColor: orvellaColors.border,
    paddingBottom: 6,
  },
  inputLabel: {
    fontSize: orvellaFontSize.sm,
    fontWeight: '600',
    color: orvellaColors.textSecondary,
    marginBottom: 4,
    marginTop: 4,
  },
  labelRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 4,
  },
  infoBox: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    backgroundColor: '#F5F7FF',
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#E0E7FF',
    gap: 10,
    marginBottom: orvellaSpacing.xs,
  },
  infoBoxText: {
    flex: 1,
    fontSize: 12,
    color: '#3B4B8C',
    lineHeight: 18,
  },
  pickerContainer: {
    backgroundColor: '#FAFAFA',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#EAECF0',
    overflow: 'hidden',
    marginBottom: orvellaSpacing.sm,
  },
  pickerError: {
    borderColor: orvellaColors.danger,
    backgroundColor: '#FFF5F5',
  },
  picker: {
    height: 50,
    color: '#1A2340',
  },
  gridRow: {
    flexDirection: 'row',
    gap: orvellaSpacing.sm,
  },
  gridCol: {
    flex: 1,
  },
  input: {
    backgroundColor: '#FAFAFA',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#EAECF0',
    paddingHorizontal: orvellaSpacing.sm,
    height: 48,
    fontSize: 13,
    color: '#1A2340',
  },
  inputError: {
    borderColor: orvellaColors.danger,
    backgroundColor: '#FFF5F5',
  },
  textArea: {
    height: 90,
    textAlignVertical: 'top',
    paddingVertical: orvellaSpacing.sm,
  },
  charCount: {
    fontSize: 10,
    color: orvellaColors.textMuted,
    textAlign: 'right',
    marginTop: 2,
  },
  imagePickerPlaceholder: {
    height: 160,
    borderWidth: 2,
    borderStyle: 'dashed',
    borderColor: orvellaColors.primary,
    borderRadius: orvellaRadius.lg,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: orvellaColors.primaryLight,
    gap: 6,
    marginBottom: orvellaSpacing.xs,
  },
  imagePickerError: {
    borderColor: orvellaColors.danger,
    backgroundColor: '#FFF5F5',
  },
  uploadIconBg: {
    width: 56,
    height: 56,
    borderRadius: orvellaRadius.full,
    backgroundColor: '#fff',
    justifyContent: 'center',
    alignItems: 'center',
    ...orvellaShadow.sm,
    marginBottom: 4,
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  modalContainer: {
    width: '100%',
    backgroundColor: '#fff',
    borderRadius: orvellaRadius.lg,
    padding: 24,
    alignItems: 'center',
    ...orvellaShadow.md,
  },
  modalIconContainer: {
    width: 64,
    height: 64,
    borderRadius: 32,
    backgroundColor: '#FFF5F5',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 16,
  },
  modalTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: orvellaColors.textPrimary,
    marginBottom: 8,
  },
  modalMessage: {
    fontSize: 14,
    color: orvellaColors.textSecondary,
    textAlign: 'center',
    marginBottom: 24,
    lineHeight: 20,
  },
  modalButton: {
    width: '100%',
    backgroundColor: orvellaColors.primary,
    paddingVertical: 14,
    borderRadius: orvellaRadius.md,
    alignItems: 'center',
  },
  modalButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },
  pickerText: {
    fontSize: orvellaFontSize.sm,
    fontWeight: 'bold',
    color: orvellaColors.textPrimary,
  },
  pickerHint: {
    fontSize: orvellaFontSize.xs,
    color: orvellaColors.textSecondary,
  },
  imagePreviewContainer: {
    height: 220,
    borderRadius: orvellaRadius.lg,
    overflow: 'hidden',
    position: 'relative',
    marginBottom: orvellaSpacing.sm,
  },
  imagePreview: {
    width: '100%',
    height: '100%',
    resizeMode: 'cover',
  },
  removeImageButton: {
    position: 'absolute',
    bottom: orvellaSpacing.sm,
    right: orvellaSpacing.sm,
    backgroundColor: orvellaColors.danger,
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: orvellaSpacing.sm,
    paddingVertical: 6,
    borderRadius: orvellaRadius.sm,
    gap: 4,
  },
  removeImageText: {
    color: '#ffffff',
    fontSize: orvellaFontSize.xs,
    fontWeight: 'bold',
  },
  progressContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: orvellaColors.primaryLight,
    padding: orvellaSpacing.sm,
    borderRadius: orvellaRadius.sm,
    marginBottom: orvellaSpacing.sm,
  },
  progressText: {
    fontSize: orvellaFontSize.xs,
    color: orvellaColors.primaryDark,
    fontWeight: '500',
  },
  buttonRow: {
    flexDirection: 'row',
    gap: orvellaSpacing.sm,
    marginTop: orvellaSpacing.sm,
  },
  halfButton: {
    flex: 1,
  },
  actionButton: {
    marginTop: orvellaSpacing.sm,
  },
  searchBoxContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: orvellaColors.surfaceVariant,
    borderRadius: orvellaRadius.md,
    borderWidth: 1.5,
    borderColor: orvellaColors.border,
    paddingHorizontal: orvellaSpacing.sm,
    height: 48,
    marginBottom: orvellaSpacing.sm,
  },
  searchIcon: {
    marginRight: 8,
  },
  searchInput: {
    flex: 1,
    fontSize: orvellaFontSize.sm,
    color: orvellaColors.textPrimary,
    height: '100%',
  },
  clearSearchBtn: {
    padding: 4,
  },
  dropdownList: {
    position: 'absolute',
    top: 52,
    left: 0,
    right: 0,
    backgroundColor: '#ffffff',
    borderRadius: orvellaRadius.md,
    borderWidth: 1.5,
    borderColor: orvellaColors.border,
    ...orvellaShadow.md,
    zIndex: 999,
    maxHeight: 200,
    overflow: 'hidden',
  },
  dropdownItem: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingVertical: 12,
    paddingHorizontal: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#F3F4F6',
  },
  dropdownItemActive: {
    backgroundColor: orvellaColors.primaryLight,
  },
  dropdownItemName: {
    fontSize: 13,
    fontWeight: 'bold',
    color: orvellaColors.textPrimary,
  },
  dropdownItemId: {
    fontSize: 10,
    color: orvellaColors.textSecondary,
    marginTop: 2,
  },
  dropdownItemEmpty: {
    padding: 16,
    alignItems: 'center',
  },
  dropdownItemEmptyText: {
    fontSize: 12,
    color: orvellaColors.textMuted,
  },
});
