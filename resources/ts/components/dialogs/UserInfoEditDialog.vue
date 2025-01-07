<script setup lang="ts">
import VueDropzone from "dropzone-vue3";
import { defineEmits, defineProps, ref, toRaw, watch, withDefaults } from 'vue';

interface UserData {
  id: string;
  first_name: string;
  last_name: string;
  username: string;
  city: string;
  email: string;
  mobile: string;
  profile_image: string;
}

interface Props {
  userData?: UserData;
  isDialogVisible: boolean;
}

interface Emit {
  (e: "submit", value: UserData): void;
  (e: "update:isDialogVisible", val: boolean): void;
}

const props = withDefaults(defineProps<Props>(), {
  userData: () => ({
    id: '0',
    first_name: '',
    last_name: '',
    city: '',
    username: '',
    mobile: '',
    profile_image: '',
    email: '',
  }),
});

const fileInput = ref<InstanceType<typeof VueDropzone> | null>(null);


const dropzoneOptions = {
  url: `${import.meta.env.VITE_API_URL}/image-upload`,
  maxFilesize: 10, // Max file size in MB
  addRemoveLinks: true,
  uploadMultiple: false,
  acceptedFiles: ".jpg, .jpeg, .png, .gif", // Accepted file types
}

const fileAdded = (addedFile: any) => {
  console.log("File added:", addedFile)
  // file.value.push(addedFile)
}

const emit = defineEmits<Emit>();

const userData = ref<UserData>(structuredClone(toRaw(props.userData)));
const imageUrl = userData.value.profile_image;
const imagePreview = ref<string | null>(imageUrl || null);
const selectedFile = ref();

watch(props, () => {
  userData.value = structuredClone(toRaw(props.userData));
  imagePreview.value = userData.value.profile_image || null;
});

function getProfileImageUrl(imagePath: string) {
  if (!imagePath) return null;
  const baseUrl = import.meta.env.VITE_API_IMAGE_PATH;
  return `${baseUrl}${imagePath}`;
}

const onImageSelect = (file: Event) => {
  console.log();
  imagePreview.value = file.dataURL;
  selectedFile.value = file;
}

const onFormSubmit = (e: Event) => {
  e.preventDefault();
  emit("update:isDialogVisible", false);
  userData.value.profile_image = selectedFile.value || null;
  emit("submit", userData.value);
};

const onFormReset = () => {
  userData.value = structuredClone(toRaw(props.userData));
  imagePreview.value = userData.value.profile_image || null;
  emit("update:isDialogVisible", false);
};

const dialogModelValueUpdate = (val: boolean) => {
  emit("update:isDialogVisible", val);
};
</script>

<template>
  <VDialog :width="$vuetify.display.smAndDown ? 'auto' : 677" :model-value="props.isDialogVisible"
    @update:model-value="dialogModelValueUpdate">
    <DialogCloseBtn @click="dialogModelValueUpdate(false)" />

    <VCard class="pa-sm-8 pa-5">
      <VCardItem class="text-center">
        <VCardTitle class="text-h5 mb-3">Update Profile</VCardTitle>
      </VCardItem>

      <VCardText>
        <!-- Image Preview with Edit Icon -->
        <div class="image-preview mb-4 text-center position-relative">
          <img v-if="imagePreview" :src="imagePreview" alt="Profile Image" class="profile-image" />
          <div v-else class="placeholder-image">No Image</div>

          <!-- Hidden File Input for Image Selection -->
          <!-- <input type="file" accept="image/*" @change="onImageSelect" ref="fileInput"  /> -->
          <VueDropzone id="dropzone" ref="fileInput" :options="dropzoneOptions" @vdropzone-success="onImageSelect"
            class="d-none" />
          <!-- Edit Icon Overlay -->
          <div class="edit-icon" @click="fileInput.$el.click()">
            <VIcon icon="mdi-image-edit-outline"></VIcon>
          </div>
        </div>

        <!-- Form -->
        <VForm class="mt-2" @submit.prevent="onFormSubmit">
          <VRow>
            <VCol cols="12" md="6">
              <AppTextField v-model="userData.first_name" label="First Name" />
            </VCol>

            <VCol cols="12" md="6">
              <AppTextField v-model="userData.last_name" label="Last Name" />
            </VCol>

            <VCol cols="12" md="6">
              <AppTextField v-model="userData.email" label="Email" />
            </VCol>

            <VCol cols="12" md="6">
              <AppTextField v-model="userData.mobile" label="Contact" />
            </VCol>

            <VCol cols="12" md="6">
              <AppTextField v-model="userData.username" label="User Name" />
            </VCol>

            <VCol cols="12" md="6">
              <AppTextField v-model="userData.city" label="Country" />
            </VCol>

            <VCol cols="12" class="d-flex flex-wrap justify-center gap-4">
              <VBtn type="submit">Submit</VBtn>
              <VBtn color="secondary" variant="tonal" @click="onFormReset">Cancel</VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </VDialog>
</template>

<style scoped lang="scss">
.image-preview {
  position: relative;
  display: inline-block;
}

.profile-image,
.placeholder-image {
  align-items: center;
  border-radius: 50%;
  block-size: 100px;
  inline-size: 100px;
  object-fit: cover;
}

.placeholder-image {
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #e0e0e0;
  color: #777;
  font-size: 0.9em;
}

.edit-icon {
  position: absolute;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background-color: transparent;
  block-size: 24px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 20%);
  cursor: pointer;
  inline-size: 24px;
  inset-block-end: 0;
  inset-inline-end: 0;
}
</style>
