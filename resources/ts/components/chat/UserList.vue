<script setup lang="ts">

interface User {
  id: number;
  first_name: string;
  last_name: string;
  full_name: string;
  profile_image?: string;
  is_online?: boolean;
  with_last_message?: {
    message?: string;
    unread_messages?: number;
  };
}

const props = defineProps({
    user: {
      type: Object as () => User,
      required: true,
    },
    selectedUser: {
      type: Object as () => User | null,
      default: null,
    },
    isChatUser: {
      type: Boolean,
      default: false,
    },
})
const emit = defineEmits<{
  (e: 'selectUser', Object:User): void;
}>();

 const isSelected = computed(() => props.selectedUser?.id === props.user.id);
 
 const avatarText = computed(() =>
      props.user.full_name
        .split(" ")
        .map((name) => name[0])
        .join("")
    );

 const lastMessage = computed(() => props.user.with_last_message?.message || "");

 const onSelect = () => {
    emit("selectUser", props.user);
  };
</script>

<template>
  <v-list-item :active="isSelected" @click="onSelect" class="rounded-lg mb-1">
    <template v-slot:prepend>
      <VBadge
        v-if="isChatUser"
        dot
        location="bottom right"
        offset-x="3"
        offset-y="3"
        bordered
        :color="user.is_online ? 'success' : 'secondary'"
      >
        <v-avatar size="40">
          <v-img v-if="user.profile_image" :src="user.profile_image" :alt="user.first_name" />
          <span v-else>{{ avatarText }}</span>
        </v-avatar>
      </VBadge>
      <v-avatar v-else size="40">
        <v-img v-if="user.profile_image" :src="user.profile_image" :alt="user.first_name" />
        <span v-else>{{ avatarText }}</span>
      </v-avatar>
    </template>
    <v-list-item-title class="text-truncate ml-2">{{ user.first_name }} {{ user.last_name }}</v-list-item-title>
    <v-list-item-subtitle class="text-truncate ml-2">{{ lastMessage }}</v-list-item-subtitle>
    <template v-slot:append>
      <v-badge
        v-if="isChatUser && user.with_last_message?.unread_messages > 0"
        :content="user.with_last_message.unread_messages"
        color="error"
        inline
      ></v-badge>
    </template>
  </v-list-item>
</template>
