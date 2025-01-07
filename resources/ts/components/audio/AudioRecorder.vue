<script setup lang="ts">
import { onUnmounted, ref } from 'vue';

const props = defineProps<{
  maxDuration?: number; // maximum recording duration in seconds
}>();

const emit = defineEmits<{
  (e: 'recordingComplete', audioBlob: Blob): void;
  (e: 'recordingStart'): void;
  (e: 'recordingStop'): void;
  (e: 'error', error: string): void;
}>();

const isRecording = ref(false);
const recordingTime = ref(0);
const mediaRecorder = ref<MediaRecorder | null>(null);
const audioChunks = ref<Blob[]>([]);
const timerInterval = ref<number | null>(null);
const analyser = ref<AnalyserNode | null>(null);
const audioContext = ref<AudioContext | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const animationFrame = ref<number | null>(null);

// Start recording
async function startRecording() {
  try {
    emit('recordingStart');
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    mediaRecorder.value = new MediaRecorder(stream);
    audioChunks.value = [];

    // Set up audio analysis
    audioContext.value = new AudioContext();
    analyser.value = audioContext.value.createAnalyser();
    const source = audioContext.value.createMediaStreamSource(stream);
    source.connect(analyser.value);
    
    analyser.value.fftSize = 256;
    const bufferLength = analyser.value.frequencyBinCount;
    const dataArray = new Uint8Array(bufferLength);

    // Draw audio visualization
    const draw = () => {
      if (!canvasRef.value || !analyser.value) return;
      
      const canvas = canvasRef.value;
      const ctx = canvas.getContext('2d');
      if (!ctx) return;

      animationFrame.value = requestAnimationFrame(draw);
      analyser.value.getByteFrequencyData(dataArray);

      // Clear canvas
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      // Draw lines
      ctx.beginPath();
      ctx.moveTo(0, canvas.height / 2);
      
      const sliceWidth = canvas.width / bufferLength;
      let x = 0;

      for (let i = 0; i < bufferLength; i++) {
        const v = dataArray[i] / 128.0;
        const y = (v * canvas.height) / 2;

        if (i === 0) {
          ctx.moveTo(x, y);
        } else {
          ctx.lineTo(x, y);
        }

        x += sliceWidth;
      }

      ctx.lineTo(canvas.width, canvas.height / 2);
      ctx.strokeStyle = '#2196F3'; // Line color
      ctx.lineWidth = 2;
      ctx.stroke();
    };

    draw();

    mediaRecorder.value.ondataavailable = (event) => {
      audioChunks.value.push(event.data);
    };

    mediaRecorder.value.onstop = () => {
      const audioBlob = new Blob(audioChunks.value, { type: 'audio/mp3' });
      emit('recordingComplete', audioBlob);
      resetRecording();
    };

    mediaRecorder.value.start();
    isRecording.value = true;
  
    startTimer();

    // Stop recording after maxDuration if specified
    if (props.maxDuration) {
      setTimeout(() => {
        if (isRecording.value) {
          stopRecording();
        }
      }, props.maxDuration * 1000);
    }
  } catch (error) {
    emit('error', 'Microphone permission denied');
    console.error('Error accessing microphone:', error);
  }
}

// Stop recording
function stopRecording() {
  if (mediaRecorder.value && isRecording.value) {
    mediaRecorder.value.stop();
    isRecording.value = false;
    emit('recordingStop');
    stopTimer();
    
    // Stop visualization
    if (animationFrame.value) {
      cancelAnimationFrame(animationFrame.value);
    }
    if (audioContext.value) {
      audioContext.value.close();
    }
    
    // Stop all audio tracks
    mediaRecorder.value.stream.getTracks().forEach(track => track.stop());
  }
}

// Timer functions
function startTimer() {
  recordingTime.value = 0;
  timerInterval.value = window.setInterval(() => {
    recordingTime.value++;
  }, 1000);
}

function stopTimer() {
  if (timerInterval.value) {
    clearInterval(timerInterval.value);
    timerInterval.value = null;
  }
}

function resetRecording() {
  recordingTime.value = 0;
  audioChunks.value = [];
}

// Format time for display
function formatTime(seconds: number): string {
  const minutes = Math.floor(seconds / 60);
  const remainingSeconds = seconds % 60;
  return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
}

// Cleanup on component unmount
onUnmounted(() => {
  if (isRecording.value) {
    stopRecording();
  }
  stopTimer();
  if (animationFrame.value) {
    cancelAnimationFrame(animationFrame.value);
  }
  if (audioContext.value) {
    audioContext.value.close();
  }
});
</script>

<template>
  <div class="audio-recorder d-flex align-center gap-4">
    <canvas 
      ref="canvasRef"
      class="waveform"
      width="300"
      height="30"
      v-show="isRecording"
    ></canvas>
    <div class="recording-controls">
      <v-btn
        :color="isRecording ? 'error' : 'primary'"
        @click="isRecording ? stopRecording() : startRecording()"
        :icon="isRecording ? 'mdi-stop' : 'mdi-microphone'"
        class="record-btn"
      >
      </v-btn>
      <span class="timer" v-if="isRecording">{{ formatTime(recordingTime) }}</span>
    </div>
    <div v-if="isRecording" class="recording-indicator">
      <span class="pulse-dot"></span>
      Recording...
    </div>
  </div>
</template>

<style scoped lang="scss">
.audio-recorder {
  display: flex;

  // flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.recording-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.record-btn {
  border-radius: 50%;
  transition: all 0.3s ease;

  &:hover {
    transform: scale(1.1);
  }
}

.timer {
  font-family: monospace;
  font-size: 1.2rem;
  min-inline-size: 4ch;
}

.recording-indicator {
  display: flex;
  align-items: center;
  color: #dc3545;
  gap: 0.5rem;
}

.pulse-dot {
  border-radius: 50%;
  animation: pulse 1.5s ease-in-out infinite;
  background-color: #dc3545;
  block-size: 10px;
  inline-size: 10px;
}

@keyframes pulse {
  0% {
    opacity: 1;
    transform: scale(1);
  }

  50% {
    opacity: 0.5;
    transform: scale(1.5);
  }

  100% {
    opacity: 1;
    transform: scale(1);
  }
}

.waveform {
  border-radius: 8px;

  // background-color: rgb(200, 200, 200);
  background-color: transparent;
  inline-size: 100%;
  margin-block: 1rem;
  max-inline-size: 300px;
}

// Optional: Add some animation to the canvas
.waveform {
  animation: fadeIn 2.5s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style> 
