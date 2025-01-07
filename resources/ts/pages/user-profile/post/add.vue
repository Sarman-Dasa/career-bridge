<script lang="ts" setup>
import AddPostDialog from '@/components/user/post/AddPostDialog.vue';
import { postRequest } from '@/services/apiService';
import { ref } from 'vue';

const isDialogOpen = ref(false)

// Open the dialog
const openPostDialog = () => {
  isDialogOpen.value = true
}

async function addNewPost(data) {
  const formData = new FormData();
  const { title, content, attachment, visibility, comment_control, status} = data;


  formData.append('title', title);
  formData.append('content', content);
  formData.append('visibility', visibility);
  formData.append('comment_control', comment_control);
  formData.append('status', status);
  if (attachment && attachment.length) {
   for (let index = 0; index < attachment.length; index++) {
    formData.append('files[]', attachment[index]);
   }
  }

  const response = await postRequest('/post/create', formData, false,{
    headers: {
    "Content-Type": 'multipart/form-data',
  },
  });

  if (response && response.status == 200) {
    console.log("response",response);
  }

}
</script>

<template>
    <!-- Button to open the post dialog -->
    <v-btn color="primary" @click="openPostDialog">Create Post</v-btn>

    <!-- Add Post Dialog -->
    <AddPostDialog v-if="isDialogOpen" :isOpen="isDialogOpen" @update:is-open="isDialogOpen = $event"
      @add-new-post="addNewPost" />
</template>



<style scoped>
/* Additional styles for the page */
</style>
