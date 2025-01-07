<script setup lang="ts">
import { defineEmits, defineProps } from 'vue';

const props = defineProps<{
  showModal: boolean;
  postSetting: Object
  isEdit: boolean
}>();

const emits = defineEmits<{
  (e: 'closeModal'): void;
  (e: 'updateSetting', value: Object): Object;
}>();

const whoCanSeePost = ref(props.postSetting.whoCanSeePost) // P = anyone(public), C = Connection only
const commentControl = ref(props.postSetting.commentControl) // A = anyone(public), C = Connection only , N = No one

function updateSetting() {
  const obj = {
    whoCanSeePost: whoCanSeePost.value,
    commentControl: commentControl.value,
  }

  emits('updateSetting', obj)
}
</script>

<template>
  <v-dialog v-model="props.showModal" max-width="300px" class="post-setting-dialog">
    <v-card>
      <v-card-title class="text-lg">
        <div class="flex items-center justify-between w-full">
          <span>Setting</span>
        </div>
      </v-card-title>
      <v-card-text>
        <div>
          <p class="mb-2 font-bold font-weight-bold">Who can see your post?</p>
          <v-radio-group v-model="whoCanSeePost" class="mb-4">
            <v-radio label="Anyone" value="P"></v-radio>
            <v-radio label="Connection only" value="C"></v-radio>
          </v-radio-group>
        </div>
        <div>
          <p class="mb-2 font-bold font-weight-bold">Comment control</p>
          <v-radio-group v-model="commentControl">
            <v-radio label="Anyone" value="A"></v-radio>
            <v-radio label="Connection only" value="C"></v-radio>
            <v-radio label="No one" value="N"></v-radio>
          </v-radio-group>
        </div>
      </v-card-text>
      <v-card-actions class="flex justify-between">
        <v-btn color="primary" @click="updateSetting" class="px-6 py-2">{{ isEdit ? 'Update' : 'Next' }}</v-btn>
        <v-btn color="secondary" @click="emits('closeModal')" class="px-6 py-2">close</v-btn>
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

.post-setting-dialog .v-card-text {
  padding-block-end: 10px !important;
}
</style>
