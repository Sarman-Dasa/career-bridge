Chat message
<script lang="ts" setup>
import { avatarText } from '@/@core/utils/formatters';
import type { Message } from '@/@types';
import FilePreview from '@/components/user/post/FilePreview.vue';
import { postRequest } from '@/services/apiService';
import VueDropzone from "dropzone-vue3";
import { onMounted, ref } from 'vue';
const props = defineProps<{
  user: User;
}>();

const messages = ref<Message[]>([])
const newMessage = ref('')
const attachments = ref<File[]>([])
const loading = ref(false)
const audioRecording = ref(false)
const users = ref<User[]>([])
const selectedUser = ref<User | null>(null)
const showFilePreview = ref(false)
const dropzoneOptions = {
  url: `${import.meta.env.VITE_API_URL}/image-upload`,
  maxFilesize: 10, // Max file size in MB
  addRemoveLinks: true,
  uploadMultiple: false,
  maxFiles: 10,
  acceptedFiles: ".jpg, .jpeg, .png, .gif", // Accepted file types
};

const fileInput = ref<InstanceType<typeof VueDropzone> | null>(null);
const isRecording = ref(false)

const fetchUsers = async () => {
  try {
    const response = await postRequest('/user/chat-user-list', {}, false)
    users.value = response.data.users
    console.log(users.value)
  } catch (error) {
    console.error(error)
  }
}

const fetchMessages = async (userId: string) => {
  loading.value = true
  try {
    const response = await postRequest('/message/receive', {
      user_id: userId
    }, false)
    messages.value = response.data.messages
  } catch (error) {
    console.error(error)
  }
  loading.value = false
}

const sendMessage = async (audioBlob?: Blob) => {
  if (!newMessage.value && attachments.value.length === 0 && !audioBlob || !selectedUser.value) return

  const formData = new FormData()
  formData.append('user_id', selectedUser.value.id)
  formData.append('message', newMessage.value)
  if (audioBlob && audioBlob.type === 'audio/wav') {
    formData.append('audio', audioBlob, 'recording.wav')
  }
  attachments.value.forEach(file => {
    formData.append('files[]', file)
  })

  try {
    const response = await postRequest('/message/send', formData, false, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    messages.value.push(response.data.message)
    newMessage.value = ''
    attachments.value = []
    showFilePreview.value = false
  } catch (error) {
    console.error(error)
  }
}

const selectUser = (user: User) => {
  selectedUser.value = user
  fetchMessages(user.id)
}

const lastMessage = (user: User) => {
  return user.with_last_message?.message || (user.with_last_message?.attachments?.length ? 'Attachment' : 'No messages yet')
}

const closeFilePreview = () => {
  attachments.value = []
  showFilePreview.value = false
}

const onFileAdded = (file: any) => {
  attachments.value.push(file)
  showFilePreview.value = true
}

const onAudioRecordingComplete = (audioBlob: Blob) => {
  if (audioBlob) {
    console.log(audioBlob)
    sendMessage(audioBlob)
  }
}

onMounted(() => {
  fetchUsers()
})
</script>

<template>
  <v-container fluid class="fill-height pa-0">
    <v-row no-gutters style="height: 100vh;">
      <!-- Users List -->
      <v-col cols="3" class="border-r">
        <v-card flat>
          <v-card-title class="py-4 px-4">
            <span class="text-h6">Messages</span>
          </v-card-title>

          <v-list class="overflow-y-auto" style="height: calc(100vh - 64px);">
            <v-list-item v-for="user in users" :key="user.id" :active="selectedUser?.id === user.id"
              @click="selectUser(user)">
              <template v-slot:prepend>
                <v-avatar size="40">
                  <v-img v-if="user.profile_image" :src="user.profile_image" :alt="user.first_name" />
                  <span v-else>{{ avatarText(user.full_name) }}</span>
                </v-avatar>
              </template>

              <v-list-item-title>{{ user.first_name }} {{ user.last_name }}</v-list-item-title>
              <v-list-item-subtitle class="text-truncate">
                {{ lastMessage(user) }}
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </v-card>
      </v-col>

      <!-- Chat Area -->
      <v-col cols="9">
        <v-card flat height="100%">
          <template v-if="selectedUser">
            <!-- Chat Header -->
            <v-card-title class="py-4 px-4 border-b d-flex mb-1">
              <v-avatar size="40" class="mr-3">
                <v-img v-if="selectedUser.profile_image" :src="selectedUser.profile_image"
                  :alt="selectedUser.first_name" />
                <span v-else>{{ avatarText(selectedUser.full_name) }}</span>
              </v-avatar>
              <div>
                <div class="text-h6">{{ selectedUser.first_name }} {{ selectedUser.last_name }}</div>
                <div class="text-subtitle-2">{{ selectedUser.email }}</div>
              </div>
            </v-card-title>

            <!-- Messages Area -->
            <v-card-text class="overflow-y-auto px-4" style="height: calc(100vh - 180px);">
              <v-progress-circular v-if="loading" indeterminate />

              <template v-else>
                <div v-for="message in messages" :key="message.id" class="mb-4">
                  <div :class="[
                    'd-flex align-center',
                    message.sender_id === selectedUser.id ? 'justify-start' : 'justify-end'
                  ]">
                    <v-avatar v-if="message.sender_id === selectedUser.id" size="32" class="mr-2">
                      <v-img v-if="selectedUser.profile_image" :src="selectedUser.profile_image"
                        :alt="selectedUser.first_name" />
                      <span v-else>{{ avatarText(selectedUser.full_name) }}</span>
                    </v-avatar>

                    <div class="d-flex flex-column" :class="[
                      message.sender_id === selectedUser.id ? 'align-start' : 'align-end'
                    ]">
                      <v-card v-if="message.message"
                        :color="message.sender_id === selectedUser.id ? 'grey-lighten-3' : 'primary'" :class="[
                          message.sender_id === selectedUser.id ? 'text-body-2' : 'white--text',
                          'message-card'
                        ]" class="pa-3 rounded-lg elevation-1 mb-2" flat>
                        <div class="message-content">
                          <p class="mb-2 text-body-1">{{ message.message }}</p>
                          <div class="d-flex align-center text-caption message-meta">
                            <span class="text-disabled">{{ message.timeAgo }}</span>
                            <v-icon v-if="message.is_edited" size="12" class="ml-1" color="grey-darken-1">
                              mdi-pencil
                            </v-icon>
                          </div>
                        </div>
                      </v-card>

                      <div v-if="message.attachments?.length" class="d-flex flex-wrap gap-2">
                        <div v-for="attachment in message.attachments" :key="attachment.id" class="attachment-wrapper">
                          <audio v-if="attachment.is_audio_file" controls :src="attachment.file_path"
                            class="audio-player rounded-lg elevation-1" />
                          <v-img v-else :src="attachment.file_path" width="100" height="100" class="rounded-lg" cover />
                        </div>
                      </div>
                    </div>

                    <v-avatar v-if="message.sender_id !== selectedUser.id" size="32" class="ml-2">
                      <v-img v-if="props.user.profile_image" :src="props.user.profile_image" />
                      <span v-else>{{ avatarText(props.user.full_name) }}</span>
                    </v-avatar>
                  </div>
                </div>
              </template>
            </v-card-text>

            <!-- Message Input -->
            <v-card-actions class="pa-4 border-t">
              <v-form @submit.prevent="sendMessage" class="w-100" v-show="!isRecording">
                <v-row align="center" no-gutters>
                  <v-col cols="auto">
                    <VueDropzone id="dropzone" ref="fileInput" :options="dropzoneOptions" @vdropzone-success="onFileAdded"
                    class="d-none" />
                    <v-btn icon variant="text" @click="fileInput.$el.click()">
                      <v-icon>mdi-paperclip</v-icon>
                    </v-btn>
                    <!-- <input ref="fileInput" type="file" multiple accept="image/*,audio/*" class="d-none"
                      @change="attachments = Array.from($event.target.files || [])"> -->
                    
                  </v-col>

                  <v-col class="px-2">
                    <v-text-field v-model="newMessage" placeholder="Type a message..." variant="outlined"
                      density="compact" hide-details />
                  </v-col>

                  <v-col cols="auto d-flex align-center gap-2">
                    <v-btn color="primary" icon @click="sendMessage">
                      <v-icon>mdi-send</v-icon>
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <AudioRecorder @recordingComplete="onAudioRecordingComplete" @recording-start="isRecording = true" @recording-stop="isRecording = false" />
            </v-card-actions>
          </template>

          <v-card-text v-else class="d-flex align-center justify-center fill-height">
            <span class="text-h6 text-medium-emphasis">Select a user to start chatting</span>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>

  <FilePreview v-if="showFilePreview" :show-modal="showFilePreview" :files="attachments" v-model:message="newMessage" :show-message-input="true" @addMoreImage="fileInput.$el.click()" @close-modal="closeFilePreview" @send="sendMessage" />
</template>

<style scoped lang="scss">
.border-r {
  border-inline-end: 1px solid rgba(0, 0, 0, 12%);
}

.border-b {
  border-block-end: 1px solid rgba(0, 0, 0, 12%);
}

.border-t {
  border-block-start: 1px solid rgba(0, 0, 0, 12%);
}
</style>
