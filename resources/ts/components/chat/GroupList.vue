<script setup lang="ts">
import type { Group } from '@/types';

const props = defineProps({
    group: {
      type: Object as () => Group,
      required: true,
    },
    selectedGroup: {
      type: Object as () => Group | null,
      default: null,
    },
})
const emit = defineEmits<{
  (e: 'selectGroup', Object:Group): void;
}>();

 const isSelected = computed(() => props.selectedGroup?.id === props.group.id);
 const lastMessage = computed(() => props.group.last_message?.message || "");
 console.log("lastMessage",lastMessage.value);
 const avatarText = computed(() =>
      props.group.name
        .split(" ")
        .map((name) => name[0])
        .join("")
    );


 const onSelect = () => {
    emit("selectGroup", props.group);
  };
</script>

<template>
  <v-list-item :active="isSelected" @click="onSelect" class="rounded-lg mb-1">
    <template v-slot:prepend>
      <v-avatar size="40">
        <v-img v-if="group.image" :src="group.image" :alt="group.name" />
        <span v-else>{{ avatarText }}</span>
      </v-avatar>
    </template>
    <v-list-item-title class="text-truncate ml-2">{{ group.name }}</v-list-item-title>
    <v-list-item-subtitle class="text-truncate ml-2">{{ lastMessage }}</v-list-item-subtitle>
    <template v-slot:append>
      <v-badge
        v-if="group.last_message?.unread_messages > 0"
        :content="group.last_message.unread_messages"
        color="error"
        inline
      ></v-badge>
    </template>
  </v-list-item>
</template>
