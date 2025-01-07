<script setup>
import { postRequest } from '@/services/apiService';
import { ref } from 'vue';

const props = defineProps({
  parentId: {
    type: String,
    required: false,
    default: null
  },
  postId: {
    type: String,
    required: true
  }
});

const emit = defineEmits(['comment-added']);

const comment = ref('');

const submitComment = async () => {
  let input = {
    post_id: props.postId,
    comment: comment.value,
    parent_id: props.parentId
  }
  let response = await postRequest(`/comment/create`, input, false);  
  if (response && response.status == 200) {
    comment.value = '';
    emit('comment-added');
  }
};
</script>
<template>
  <v-container>
    <v-row>
      <v-col cols="12" class="mx-auto">
        <v-card class="pa-4">
          <v-textarea
            v-model="comment"
            label="Add your comment"
            auto-grow
            outlined
            dense
            clearable
            rows="1"
            class="mb-4"
          ></v-textarea>

          <v-btn
            color="primary"
            @click="submitComment"
            :disabled="!comment.trim()"
            class="float-end"
          >
            Add Comment
          </v-btn>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>



<style scoped lang="scss">
@import "../../../styles/styles"
</style>
