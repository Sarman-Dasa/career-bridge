<script setup lang="ts">
import { avatarText } from '@/@core/utils/formatters';
import { useUserStore } from '@/pages/user-profile/useUserStore';
import { deleteRequest, postRequest, putRequest } from '@/services/apiService';
import Swal from "sweetalert2";
import { useRoute, useRouter } from 'vue-router';
import type { Post, PostSetting } from '../type';

const route = useRoute()
const router = useRouter()
const userStore = useUserStore();

interface Props {
  post: Post
}

interface Emit {
  (e: 'getPostList'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emit>()

const items = ref([
  { title: 'Edit Post', value: 'edit', icon: 'mdi-pencil' },
  { title: 'Delete Post', value: 'delete', icon: 'mdi-delete' },
  { title: 'Post Setting', value: 'postSetting', icon: 'mdi-cog' }
])

const isOpenPostSettingModal = ref(false)
const isOpenEditPostDialog = ref(false)
const postSetting = ref<PostSetting>({
  whoCanSeePost: props.post.visibility,
  commentControl: props.post.comment_control,
})

const isLoginUser = computed(() => {
  return userStore.user?.id == props.post.user_id;
})

function getProfileImageUrl(imagePath: string) {
  if (!imagePath) return null;
  const baseUrl = import.meta.env.VITE_API_IMAGE_PATH;
  return `${baseUrl}${imagePath}`;
}


function openPost(id: string) {
  router.push({ name: 'PostDetail', params: { id: id } })
}

async function updatePostLike(id: string) {
  const response = await postRequest(`/post/like-dislike/${id}`, {}, false);

  if (response && response.status == 200) {
    let data = response.data;
    emit('getPostList')
  }
}

function handleItemClick(item: { title: string; value: string }) {
  console.log('Clicked item:', item);
  if (item.value === 'edit') {
    isOpenEditPostDialog.value = true
  } else if (item.value === 'delete') {
    deletePostConfirmation(props.post.id)
  } else if (item.value === 'postSetting') {
    console.log('postSetting: ');
    isOpenPostSettingModal.value = true
    postSetting.value.commentControl = props.post.comment_control
    postSetting.value.whoCanSeePost = props.post.visibility
  }
}

async function deletePost(id: string) {
  console.log('id: ', id);
  const response = await deleteRequest(`/post/delete/${id}`, {});
  if (response && response.status == 200) {
    emit('getPostList')
  }
}

async function setPostSetting(data: PostSetting) {
  postSetting.value = data

  let input = {
    visibility: data.whoCanSeePost,
    comment_control: data.commentControl
  }

  const response = await putRequest(`/post/update/${props.post.id}`, input);

  if (response && response.status == 200) {
    emit('getPostList')
  }

  isOpenPostSettingModal.value = false
}

async function updatePost(post: Post) {
  let input = {
    title: post.title,
    content: post.content,
    visibility: post.visibility,
    comment_control: post.comment_control,
  }

  const response = await putRequest(`/post/update/${props.post.id}`, input);

  if (response && response.status == 200) {
    emit('getPostList')
  }

  isOpenEditPostDialog.value = false
}

async function deletePostConfirmation(id: string) {
  Swal.fire({
    title: "Are You sure To Delete Post.",
    text: "Record can't retrive !",
    icon: "question",
    showCancelButton: true,
    confirmButtonText: "Yes",
    confirmButtonColor: "primary",
  }).then(response => {
    if (response.isConfirmed) {
      deletePost(id)
    }
  })
}


</script>
<template>
  <v-card class="post-card mx-auto">
    <!-- Post Header -->
    <v-card-title class="text-h6 text-truncate d-flex justify-space-between">
      <div class="d-flex">
        <v-avatar size="40" class="mr-3">
          <v-img v-if="props.post?.user?.profile_image" :src="props.post?.user?.profile_image" ></v-img>
          <span v-else>{{ avatarText(props.post?.user?.full_name) }}</span>
        </v-avatar>
        <div class="d-flex flex-column">
          <span class="font-weight-bold">{{ props.post?.user?.full_name }}</span>
          <span class="text-caption">{{ $formatDate(props.post?.created_at) }}</span>
        </div>
      </div>
      <MoreBtn v-if="isLoginUser" :menuList="items" @item-click="handleItemClick" />
    </v-card-title>


    <!-- Post Content -->
    <v-card-text class="text-body-2 text-truncate mt-3">
      <p>{{ post.title }}</p>
      <p>{{ post.content }}</p>
    </v-card-text>

    <!-- Attachments as Slider -->
    <!-- <v-divider></v-divider> -->
    <v-card-text v-if="post.attachments?.length" class="px-0">
      <v-carousel v-if="post.attachments?.length" height="auto" class="post-carousel" cycle hide-delimiter-background
        :show-arrows="post.attachments?.length > 1">
        <v-carousel-item v-for="attachment in post.attachments" :key="attachment.id"
          :src="attachment.file_path" cover>
        </v-carousel-item>
      </v-carousel>
    </v-card-text>

    <div class="like-comment-count-list">
        <p>
          <VIcon icon="mdi-thumb-up" size="18"></VIcon>
          <span class="ml-1">{{ post.likeCount }}</span>
        </p>
        <p><strong>Comments:</strong> {{ post.commentCount }}</p>
      </div>
    <!-- Actions -->
    <v-divider></v-divider>
    <v-card-text class="action-icons">
      <div class="icon-list" @click="updatePostLike(post.id)">
        <VIcon v-if="!post.hasLike" icon="mdi-thumbs-up-outline"></VIcon>
        <VIcon v-else icon="mdi-thumbs-up" color="primary"></VIcon>
        <span class="text-xs">Like</span>
      </div>
      <div class="icon-list" @click="openPost(post.id)" v-if="post.hasCommentAllowed">
        <VIcon icon="mdi-comment-text-outline"></VIcon>
        <span class="text-xs">Comment</span>
      </div>
      <div class="icon-list">
        <VIcon icon="mdi-repeat-variant"></VIcon>
        <span class="text-xs">Share</span>
      </div>
      <div class="icon-list">
        <VIcon icon="mdi-send-variant"></VIcon>
        <span class="text-xs">Send</span>
      </div>
    </v-card-text>
  </v-card>
  <PostSetting :show-modal="isOpenPostSettingModal" :post-setting="postSetting" :is-edit="true"
    @close-modal="isOpenPostSettingModal = false" @update-setting="setPostSetting" />
  
  <EditPostDialog :isOpen="isOpenEditPostDialog" :post="props.post" @update:isOpen="isOpenEditPostDialog = false" @updatePost="updatePost"/>
</template>
