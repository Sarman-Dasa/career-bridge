<script setup lang="ts">
import { defineEmits, defineProps, ref, watch } from 'vue';
import { VBtn, VCard, VCardActions, VCardText, VCardTitle, VDialog, VImg, VTextField } from 'vuetify/components';

interface FileWithPreview extends File {
  previewUrl: string;
}

const props = defineProps<{
  showModal: boolean;
  files: File[];
  showMessageInput: {
    type: Boolean,
    default: false,
    required: false,
  },
  message: {
    type: String,
    default: '',
    required: false,
  },
}>();

const emits = defineEmits<{
  (e: 'closeModal'): void;
  (e: 'send'): void;
  (e: 'addMoreImage'): void;
  (e: 'update:message', value: string): void;
}>();

const filesWithPreview = ref(props.files);
const newMessage = ref(props.message);

function closeModal() {
  emits('closeModal');
}
function getPreviewUrl(file) {
  console.log('file11: ', file);
  return file.dataURL
    // return URL.createObjectURL(file)
}

watch(newMessage, (value) => {
  emits('update:message', value);
});
</script>

<template>
  <v-dialog v-model="props.showModal" max-width="800px" persistent>
    <v-card>
      <v-card-title class="text-lg">
        <div class="flex items-center justify-between w-full">
          <span>Selected Images</span>
        </div>
      </v-card-title>
      <v-card-text>
        <v-row>
          <v-col
            v-for="(file, index) in filesWithPreview"
            :key="index"
            cols="12" sm="4" md="4"
          >
            <v-img
              :src="getPreviewUrl(file)"
              alt="Image preview"
              class="w-full h-40 object-cover rounded-lg shadow-md"
            />
          </v-col>
        </v-row>
        <v-row v-if="showMessageInput">
          <v-col cols="12">
            <v-text-field
              v-model="newMessage"
              label="Add a message"
              placeholder="Type your message here..."
              variant="outlined"
              density="comfortable"
            />
          </v-col>
        </v-row>
      </v-card-text>
      <v-card-actions class="flex justify-between">
        <v-btn color="secondary" @click="closeModal" class="px-6 py-2">Cancel</v-btn>
        <v-btn color="primary" @click="emits('addMoreImage')" class="px-6 py-2">Add new File</v-btn>
        <v-btn color="success" @click="emits('send')" class="px-6 py-2">Next</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<style scoped>
/* Custom styles */
.v-card-title {
  border-block-end: 1px solid #e0e0e0;
}

.v-dialog {
  border-radius: 12px;
}
</style>
