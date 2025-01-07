<script setup lang="ts">
import PostList from '@/components/postComment/PostList.vue';
import type { Post } from '@/components/type';
import { getRequest, postRequest } from '@/services/apiService';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute()
const router = useRouter()
const postLoader = ref<boolean>(true)
const isLocalDialogVisible = ref<boolean>(false)
const post = ref<Post>({} as Post)

async function getPostList() {
  postLoader.value = true
  let id = route.params.id;
  const response = await getRequest(`/post/view/${id}`);

  if (response && response.status == 200) {
    let data = response.data as { post: Post };
    post.value = data.post;
  }
  postLoader.value = false
}

const comments = ref([]);
const parentId = ref<string | null>(null);
const fullName = ref<string | null>(null);

async function getComments() {
  let input = {
    post_id: route.params.id
  }
  let response = await postRequest('/comment', input, false);
  if (response && response.status == 200) {
    comments.value = response.data.comments;
  }
}

async function like(commentId: string) {
  let response = await postRequest(`/comment/like-dislike/${commentId}`, {}, false);
  if (response && response.status == 200) {
    getComments();
  }
}

function reply(comment: any) {
  isLocalDialogVisible.value = true;
  parentId.value = comment.id;
  fullName.value = comment.user.full_name;
}

function closeDialog() {
  isLocalDialogVisible.value = false;
  parentId.value = null;
  fullName.value = null;
}

function commentAdded() {
  closeDialog();
  getComments();
  getPostList();
}

onMounted(() => {
  getPostList()
  getComments()
})

</script>

<template>
  <div>
    <v-row>
      <v-col cols="12" md="4">
        <v-btn class="back-btn" @click="router.back()">
          <v-icon icon="mdi-arrow-left"></v-icon>
        </v-btn>
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="12" md="4" class="post_card py-5">
        <PostList :post="post" @get-post-list="getPostList" />
        <AddComment :post-id="route.params.id" @comment-added="commentAdded" />
      </v-col>
      <v-col cols="12" md="8">
        <CommentList :comments="comments" @like="like" @reply="reply" />
        <!-- <AddComment :post-id="route.params.id" @comment-added="commentAdded" /> -->
      </v-col>
    </v-row>

    <VDialog max-width="600" :model-value="isLocalDialogVisible" @update:model-value="closeDialog"
      @keyup.esc="closeDialog">
      <v-card>
        <DialogCloseBtn @click="closeDialog" />
      <v-card-title>
        <h4>Reply to {{ fullName }}</h4>
      </v-card-title>
      <AddComment :parent-id="parentId" :post-id="route.params.id" @comment-added="commentAdded" />
      </v-card>
    </VDialog>
  </div>
</template>
<style lang="scss">
@import "../../../../styles/styles";

.back-btn {
  position: absolute;
  z-index: 1000;
  padding: 10px;
  border: 1px solid #e0e0e0;
  background-color: #fff;
  box-shadow: 0 0 10px 0 rgba(0, 0, 0, 10%);
  cursor: pointer;

  // inset-block-start: 10px; /* Keeps the button 10px from the top */
  inset-inline-end: 10px;

  /* Moves the button 10px from the right */
  transition: all 0.3s ease;
}

.back-btn:hover {
  background-color: #f0f0f0;
}

.add_comment_dialog {
  padding: 10px;
  border-radius: 12px;
  background-color: #fff;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 10%);
}
</style>
