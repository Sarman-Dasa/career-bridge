<script setup lang="ts">
import MoreBtn from '@/@core/components/MoreBtn.vue';
import { avatarText } from '@/@core/utils/formatters';
import type { Message, User } from '@/types';
import { defineEmits, defineProps, nextTick, ref } from 'vue';

const props = defineProps<{
  messages: Message[];
  selectedUser: User;
  loggedInUser: User;
  loading: boolean;
  isLoadingMore: boolean;
  isGroupMessage?:boolean;
}>();

const emit = defineEmits<{
  (e: 'loadMore', $state: any): void;
  (e: 'deleteAttachment', messageId: string, attachmentId: string): void;
  (e: 'itemClick', item: any, message: Message): void;
}>();

const items = ref([
  { title: 'Edit Message', value: 'edit', icon: 'mdi-pencil' },
  { title: 'Delete Message', value: 'delete', icon: 'mdi-delete' },
]);
const messagesContainer = ref();

function showNewMessage() {
   nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTo({
        top: messagesContainer.value.scrollHeight,
        behavior: "smooth",
      });
    }
  });
}

const formatDateLabel = (formattedDate: string) => {
  const today = new Date();
  const yesterday = new Date();
  yesterday.setDate(today.getDate() - 1);

  // Convert "10 Feb 2025" to a Date object for comparison
  const dateParts = formattedDate.split(" ");
  const day = parseInt(dateParts[0], 10);
  const month = new Date(Date.parse(dateParts[1] + " 1, 2025")).getMonth(); // Get numeric month
  const year = parseInt(dateParts[2], 10);
  const date = new Date(year, month, day);

  // Format today and yesterday to match the stored format ("10 Feb 2025")
  const todayStr = today.toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });
  const yesterdayStr = yesterday.toLocaleDateString("en-GB", { day: "numeric", month: "short", year: "numeric" });

  if (formattedDate === todayStr) {
    return "Today";
  } else if (formattedDate === yesterdayStr) {
    return "Yesterday";
  }

  // Get difference in days
  const diffDays = Math.floor((today.getTime() - date.getTime()) / (1000 * 60 * 60 * 24));

  if (diffDays <= 7) {
    return date.toLocaleDateString("en-GB", { weekday: "long" }); // Show day name (e.g., "Monday")
  }

  // Return the original formatted date for older messages
  return formattedDate;
};




defineExpose({showNewMessage})
</script>

<template>
 <div v-bind="$attrs">
    <div class="message-list pa-5" ref="messagesContainer">
      <infinite-loading class="loader text-center" top  v-bind="$attrs">
        <template #complete>
          <span class="text-grey">No more messages to load</span>
        </template>
        <template #spinner>
          <v-progress-circular indeterminate v-if="isLoadingMore" />
        </template> 
      </infinite-loading>
    <div v-if="loading">loader</div>
      <template v-else>
        <div v-for="date in Object.keys(messages)" :key="messageKey" class="message-group">
            <v-chip color="amber" variant="elevated" class="px-4 py-2" style="left:45%">
              {{formatDateLabel(date) }}
            </v-chip>
          <div v-for="message in messages[date]" :key="message.id" class="mb-4">
          <div :class="[
            'd-flex',
            message.sender_id !== loggedInUser?.id ? 'justify-start' : 'justify-end'
          ]">
            <!-- Sender Avatar -->
            <!-- For Group chat -->
            
            <v-avatar v-if="isGroupMessage && message.sender_id !== loggedInUser?.id" size="32" class="mr-2">
              <v-img v-if="message.sender.profile_image" :src="message.sender.profile_image" :alt="message.sender" />
              <span v-else>{{ avatarText(message.sender.full_name) }}</span>
            </v-avatar>

            <!-- For user chat -->
            <v-avatar v-if="!isGroupMessage && message.sender_id !== loggedInUser?.id" size="32" class="mr-2">
              <v-img v-if="selectedUser.profile_image" :src="selectedUser.profile_image" :alt="selectedUser.first_name" />
              <span v-else>{{ avatarText(selectedUser.full_name) }}</span>
            </v-avatar>

            <div class="d-flex flex-column" :class="[
              message.sender_id !== loggedInUser?.id ? 'align-start' : 'align-end'
            ]">
              <!-- Message Content -->
              <v-card v-if="message.message"
                :class="[message.sender_id !== loggedInUser?.id ? 'received' : 'send', 'message-card']"
                class="mb-2" flat>
                <div class="message-content">
                  <div class="d-flex justify-space-between">
                    <p class="text-body-1 ma-0">{{ message.message }}</p>
                    <MoreBtn v-if="message.sender_id === loggedInUser?.id" :menuList="items"
                      @item-click="(item) => emit('itemClick', item, message)" :size="12" :icon="'tabler-settings'"  class="setting"/>
                  </div>
                  <!-- Message Meta -->
                  <div class="d-flex align-center text-caption message-meta">
                    <span class="text-disabled">{{ message.timeAgo }}</span>
                    <v-icon v-if="message.is_edited" size="12" class="ml-1" color="grey-darken-1">mdi-pencil</v-icon>
                    <div v-if="message.sender_id === loggedInUser?.id" class="message-status ml-1">
                      <v-icon v-if="message.is_seen" size="12" color="success">mdi-check-circle</v-icon>
                      <v-icon v-else-if="message.is_delivered" size="12" color="info">mdi-check-all</v-icon>
                      <v-icon v-else-if="message.is_sent" size="12" color="grey">mdi-check</v-icon>
                    </div>
                  </div>
                </div>
              </v-card>

              <!-- Attachments -->
              <div v-if="message.attachments?.length" class="d-flex flex-wrap gap-2">
                <div v-for="attachment in message.attachments" :key="attachment.id"
                  class="attachment-wrapper position-relative hover-container">
                  <audio v-if="attachment.is_audio_file" controls :src="attachment.file_path"
                    class="audio-player rounded-lg elevation-1" />
                  <v-img v-else :src="attachment.file_path" width="100" height="100" class="rounded-lg" cover />
                  <div class="delete-icon position-absolute">
                    <v-btn icon="mdi-delete" size="x-small" color="error" variant="tonal"
                      @click="emit('deleteAttachment', message.id, attachment.id)">
                    </v-btn>
                  </div>
                </div>
              </div>
            </div>

            <!-- Receiver Avatar -->
            <v-avatar v-if="message.sender_id === loggedInUser?.id" size="32" class="ml-2">
              <v-img v-if="loggedInUser.profile_image" :src="loggedInUser.profile_image" />
              <span v-else>{{ avatarText(loggedInUser.full_name) }}</span>
            </v-avatar>
          </div>
        </div>
      </div>
      </template>
    </div>
 </div>
</template>

<style scoped lang="scss">
.message-list {
  overflow-y: auto;
  height: calc(100vh - 400px);
  scrollbar-width: 0;

  &::-webkit-scrollbar {
    inline-size: 0;
  }

  &::-webkit-scrollbar-track {
    border-radius: 10px;
    background: #f1f1f1;
  }
}

.hover-container {
  position: relative;

  .delete-icon {
    z-index: 1;
    display: none;
    inset-block-start: 4px;
    inset-inline-end: 4px;
  }

  &:hover .delete-icon {
    display: block;
  }
}

.send
{
  background-color: #587cc3;
    padding: 10px;
    border-radius: 15px;

    .more_btn .v-btn {
      display: none;
      height: 15px !important;
      width: 20px !important;
    }
}

.received
{
  background-color: #5e8fee;
    padding: 10px;
    border-radius: 15px;
}
</style> 
