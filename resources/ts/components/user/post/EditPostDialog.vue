<script setup lang="ts">
import type { Attachment, PostSetting } from '@/components/type';
import { ref } from "vue";

// Props
const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true,
  },
  post: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits<{
  (e: 'update:isOpen', value: boolean): void;
  (e: 'updatePost', data: any): void;
}>();

// State
const isDialogOpen = ref(props.isOpen);
const postContent = ref(props.post.content);
const title = ref(props.post.title);



const postAttachment = ref<Attachment[]>(props.post.attachments);

const isOpenPostSettingModal = ref(false)
const postSetting = ref<PostSetting>({
  whoCanSeePost: props.post.visibility,
  commentControl: props.post.comment_control,
})


// Methods
const closeDialog = () => {
  isDialogOpen.value = false;
  emit("update:isOpen", false);
};


const submitPost = () => {
  if (postContent.value.trim() === "") {
    return;
  }

  const postData = {
    content: postContent.value,
    title: title.value,
    visibility: postSetting.value.whoCanSeePost,
    comment_control: postSetting.value.commentControl,
  };

  emit('updatePost', postData);
  closeDialog();
};

function setPostSetting(data: PostSetting) {
  postSetting.value = data
  isOpenPostSettingModal.value = false
}

watch(
  () => props.isOpen,
  (newVal) => {
    isDialogOpen.value = newVal;
  }
);
</script>

<template>
  <v-dialog v-model="isDialogOpen" max-width="600px">
    <DialogCloseBtn @click="closeDialog" />
    <v-card>
      <v-card-title class="d-flex justify-space-between">
        <span class="headline">Update a Post</span>
        <VIcon icon="mdi-cog-outline" size="18" class="mr-3 mt-1" @click="isOpenPostSettingModal = true"></VIcon>
      </v-card-title>

      <v-card-text>
        <v-text-field v-model="title" label="title" outlined> </v-text-field>
        <!-- Post Content Input -->
        <v-textarea v-model="postContent" label="What's on your mind?" outlined rows="4" class="mt-4"></v-textarea>

        <!-- Edit Icon Overlay -->
        <div class="edit-icon mt-4 d-flex justify-space-between">
          <!-- Small card for preview image show -->
          <v-row class="flex-wrap justify-end" dense>
            <!-- Display up to 3 images -->
            <v-col v-for="(file, index) in postAttachment.slice(0, 3)" :key="index" cols="auto"
              class="d-flex justify-center align-center" style="padding: 0; margin-left: -10px;">
              <v-img :src="file.file_path" alt="Image preview" class="small-image-preview" contain />
            </v-col>

            <!-- Show count of additional images -->
            <div v-if="postAttachment?.length > 3" class="preview-image-count">
              +{{ postAttachment.length - 3 }}
            </div>
          </v-row>
        </div>
      </v-card-text>
      <v-card-actions>
        <v-spacer></v-spacer>
        <v-btn text @click="closeDialog">Cancel</v-btn>
        <v-btn color="primary" @click="submitPost">Post</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
  <PostSetting :show-modal="isOpenPostSettingModal" :post-setting="postSetting"
    @close-modal="isOpenPostSettingModal = false" @update-setting="setPostSetting" />
</template>

<style scoped lang="scss">
.small-image-preview {
  /* Rotate the images slightly */
  overflow: hidden;
  border: 2px solid white;
  border-radius: 10px;
  block-size: 24px;
  cursor: pointer;
  inline-size: 24px;
  transform: rotate(15deg);
}

.preview-image-count {
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background-color: rgba(0, 0, 0, 60%);
  block-size: 34px;
  color: white;
  font-size: 12px;
  inline-size: 34px;
  margin-inline-start: -10px;
}
</style>
