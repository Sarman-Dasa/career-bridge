<script lang="ts" setup>
import { avatarText } from '@/@core/utils/formatters';
import GroupList from '@/components/chat/GroupList.vue';
import MessageList from '@/components/chat/MessageList.vue';
import UserListItem from '@/components/chat/UserList.vue';
import FilePreview from '@/components/user/post/FilePreview.vue';
import echo from '@/plugins/echo';
import { useDebouncedRef } from '@/ref/debouncedRef';
import { deleteRequest, postRequest, putRequest } from '@/services/apiService';
import type { Group, Message, User } from '@/types';
import VueDropzone from "dropzone-vue3";
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { useUserStore } from '../user-profile/useUserStore';
// import InfiniteLoading from 'vue-infinite-loading';

const props = defineProps<{
  user: User;
}>();

const userStore = useUserStore();

const loggedInUser = computed(() => userStore.user);
let target = ref(".message-list");
const resetData = ref(false)
const distance = ref(5)

const messages = ref<Record<string, Message[]>>({});
const newMessage = ref('')
const attachments = ref<File[]>([])
const loading = ref(false)
const selectedUser = ref<User | Group | null>(null)
const showFilePreview = ref(false)
const isTyping = ref(false)
const typingTimeout = ref<NodeJS.Timeout>()
const chatUsers = ref<User[]>([])
const contactUsers = ref<User[]>([])
const chatUsersCount = ref(0)
const contactUsersCount = ref(0)
const isSendingMessageLoading = ref(false)
const page = ref(1)
const perPage = ref(50)
const isLoadingMore = ref(false)
const totalPages = ref(0)
const childRef = ref(null);
const isMobile = ref(false); // Detect mobile view
const isChatOpen = ref(false); // Toggle between user list and chat
const search = useDebouncedRef(null,500);
const isGroupMessage = ref(false)
const typingGroupMember = ref()

// Set default tab if not defined
const sidebarActiveTab = ref('user')

// tabs
const tabs = [
  { title: 'User', icon: 'tabler-user', tab: 'user' },
  { title: 'Group', icon: 'tabler-users', tab: 'group' },
]

const groups = ref<Group[]>([])
const groupsCount = ref(0) 

const dropzoneOptions = {
  url: `${import.meta.env.VITE_API_URL}/image-upload`,
  maxFilesize: 10,
  addRemoveLinks: true,
  uploadMultiple: false,
  maxFiles: 10,
  acceptedFiles: ".jpg, .jpeg, .png, .gif",
};

const fileInput = ref<InstanceType<typeof VueDropzone> | null>(null);
const isRecording = ref(false)

const items = ref([
  { title: 'Edit Message', value: 'edit', icon: 'mdi-pencil' },
  { title: 'Delete Message', value: 'delete', icon: 'mdi-delete' },
])

const editMessageId = ref<string | null>(null)

const fetchUsers = async () => {
  try {
    let input = {
      search:search.value
    }
    const response = await postRequest('/user/chat-user-list', input, false)
    chatUsers.value = response.data.chat_users
    contactUsers.value = response.data.contact_users
    chatUsersCount.value = response.data.chat_users_count
    contactUsersCount.value = response.data.contact_users_count
  } catch (error) {
    console.error(error)
  }
}

const fetchGroups = async () => {
  try {
    let input = {
      search:search.value
    }
    const response = await postRequest('/group', input, false)
    groups.value = response.data.groups
    groupsCount.value = response.data.count
  } catch (error) {
    console.error(error)
  }
}

const loadMoreMessages = async ($state: any) => {

  try {
    isLoadingMore.value = true;

    let response;
    if (sidebarActiveTab.value === 'group') {
      response = await postRequest('/message/group/receive-message',{
        group_id:selectedUser.value?.id,
        per_page: perPage.value,
        page: page.value
      },false);
    } else {
      response = await postRequest('/message/receive', {
        user_id: selectedUser.value?.id,
        per_page: perPage.value,
        page: page.value
      }, false);
    }

    const newMessages = response.data.messages;
    totalPages.value = response.data.total_pages
    
    if (Object.keys(newMessages).length) {
         // Ensure reactivity is preserved
         messages.value = {
        ...newMessages, // Add new messages
        ...messages.value // Keep old messages
      };

      // messages.value.unshift(...newMessages); // Push messages with key-value p
      if (page.value < totalPages.value) {
        page.value++;
        $state?.loaded();
      }
      else {
        $state?.complete();
      }
        // Mark messages as seen if they're unseen and not from logged-in user
        const unseenMessages = Object.values(newMessages)
        .flat()
        .filter((m: Message) => !m.is_seen && m.sender_id !== loggedInUser.value?.id)
        .map((m: Message) => m.id);

      if (unseenMessages.length && sidebarActiveTab.value === 'user') {
        markAsSeen(unseenMessages);
      }
    } else {
      $state?.complete();
    }

  } catch (error) {
    console.error(error);
    $state?.error();
  } finally {
    isLoadingMore.value = false;
  }
};

const sendMessage = async (audioBlob?: Blob) => {
  isSendingMessageLoading.value = true
  if (!newMessage.value && attachments.value.length === 0 && !audioBlob || !selectedUser.value) return

  const formData = new FormData()
  if(sidebarActiveTab.value === 'user')
    formData.append('user_id', selectedUser.value.id)
  else
    formData.append('group_id', selectedUser.value.id)
  formData.append('message', newMessage.value)
  if (audioBlob && audioBlob.type === 'audio/mp3') {
    formData.append('audio', audioBlob, 'recording.wav')
  }
  attachments.value.forEach(file => {
    formData.append('files[]', file)
  })

  const URL = sidebarActiveTab.value === "user" ? '/message/send' : '/message/group/send'
  try {
    const response = await postRequest(URL, formData, false, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    const message = response.data.message
    
    // Get today's date as key
    const today = new Date().toLocaleDateString('en-GB', { 
      day: 'numeric',
      month: 'short', 
      year: 'numeric'
    }).replace(',', ''); // Remove the comma if needed

    // Initialize today's array if it doesn't exist
    if (!messages.value[today]) {
      messages.value[today] = [];
    }

    // Add new message to today's group
    messages.value[today].push(message);

    newMessage.value = ''
    attachments.value = []
    showFilePreview.value = false

    // Update last message for selected user
    if (selectedUser.value) {
      selectedUser.value.last_message = {
        message: message.message,
        created_at: message.created_at,
        attachments: message.attachments
      };
    }

    childRef?.value?.showNewMessage(); // Call the child function to show lates message 
    isSendingMessageLoading.value = false
  } catch (error) {
    console.error(error)
  }
}

// Update message
const updateMessage = async () => {
  try {
    const response = await putRequest(`/message/update/${editMessageId.value}`, {
      message: newMessage.value
    });
    
    // Find the message group that contains the message
    const dateKey = Object.keys(messages.value).find(date =>
      messages.value[date].some((m: Message) => m.id === editMessageId.value)
    );

    if (dateKey) {
      const index = messages.value[dateKey].findIndex((m: Message) => m.id === editMessageId.value);
      if (index !== -1) {
        messages.value[dateKey][index] = response.data.data.message;
      }
    }
    editMessageId.value = null;
    newMessage.value = '';
  } catch (error) {
    console.error('Error updating message:', error);
  }
};

// Delete message
const deleteMessage = async (messageId: string) => {
  try {
    await deleteRequest(`/message/delete/${messageId}`);
    
    const dateKey = Object.keys(messages.value).find(date =>
      messages.value[date].some((m: Message) => m.id === messageId)
    );

    if(dateKey) {
      const index = messages.value[dateKey].findIndex((m: Message) => m.id === messageId);

      if (index !== -1) {
        messages.value[dateKey].splice(index, 1);
        
        // Remove date group if empty
        if (messages.value[dateKey].length === 0) {
          delete messages.value[dateKey];
        }
        
        // Update last message if needed
        if (selectedUser.value) {
          const lastDateKey = Object.keys(messages.value).pop();
          if (lastDateKey) {
            const lastMessageGroup = messages.value[lastDateKey];
            const lastMessage = lastMessageGroup[lastMessageGroup.length - 1];
            selectedUser.value.last_message = {
              message: lastMessage.message,
              created_at: lastMessage.created_at,
              attachments: lastMessage.attachments
            };
          }
        }
      }
    }
  } catch (error) {
    console.error('Error deleting message:', error);
  }
};

const deleteMessageAttachment = async (messageId: string, attachmentId: string) => {
  try {
    let response = await deleteRequest(`/message/delete-attachment/${messageId}/${attachmentId}`);
    if (response && response.status == 200) {
      // Find message in the correct date group
      for (const [date, messageGroup] of Object.entries(messages.value)) {
        const messageIndex = messageGroup.findIndex(message => message.id === messageId);
        if (messageIndex !== -1) {
          if (!response.data.message?.attachments?.length) {
            // Remove message if no attachments left
            messages.value[date].splice(messageIndex, 1);
            // Remove date group if empty
            if (messages.value[date].length === 0) {
              delete messages.value[date];
            }
          } else {
            // Update attachments if message still exists
            messages.value[date][messageIndex].attachments = response.data.message.attachments;
          }
          break;
        }
      }
    }
  } catch (error) {
    console.error('Error deleting message attachment:', error);
  }
};

const selectUser = (user: User) => {
  selectedUser.value = user;
  page.value = 1;
  messages.value = {};
  totalPages.value = 0;
  resetData.value = !resetData.value;
  isChatOpen.value = true;
  isGroupMessage.value = false
  // loadMoreMessages();
}

const selectGroup = (group: User) => {
  selectedUser.value = group;
  page.value = 1;
  messages.value = {}
  totalPages.value = 0;
  resetData.value = !resetData.value;
  isChatOpen.value = true;
  isGroupMessage.value = true
  setGroupChannel()
}

const closeChat = () => {
  selectedUser.value = null;
  isChatOpen.value = false;
};

const lastMessage = (user: User) => {
  return user.last_message?.message || (user.last_message?.attachments?.length ? 'Attachment' : 'No messages yet')
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
    sendMessage(audioBlob)
  }
}

const emitTyping = () => {
  if (selectedUser.value) {
    if(!isGroupMessage.value) {
      echo.private(`chat.${selectedUser.value.id}`).whisper('typing', {
        user: loggedInUser.value
      });
    }
    else {
      echo.private('group.messages').whisper('typing', {
        user: loggedInUser.value
      });
    }
  }
}

const handleTyping = () => {
  if (typingTimeout.value) {
    clearTimeout(typingTimeout.value)
  }

  emitTyping()

  typingTimeout.value = setTimeout(() => {
    if (selectedUser.value) {
      if(!isGroupMessage.value) {
        echo.private(`chat.${selectedUser.value.id}`).whisper('stopTyping', {
          user: loggedInUser.value
        });
      }
      else {
        echo.private(`group.messages`).whisper('stopTyping', {
          user: loggedInUser.value
        });
      }
    }
  }, 1000)
}

const updateMessageStatus = (message: Message, status: string) => {
  echo.private(`chat.${selectedUser.value.id}`).whisper('messageStatus', {
    message: message,
    status: status,
    user_id: loggedInUser.value?.id
  });
}

const markAsSeen = async (messageIds: string[]) => {
  const response = await postRequest('/message/mark-seen', { message_ids: messageIds }, false);

  if (response.status === 200) {
    messageIds.forEach(messageId => {
      const dateKey = Object.keys(messages.value).find(date =>
          messages.value[date].some((m: Message) => m.id === messageId)
        );

        if(dateKey) {
            const message = messages.value[dateKey].find((m: Message) => m.id === messageId);
        if (message) {
         
            message.is_seen = true;
            message.seen_at = new Date().toISOString();
            selectedUser.value.last_message = {
              unread_messages: 0
            }
        }
      }
      });
  } else {
    console.error('Failed to mark messages as seen');
  }
}

const markAsDelivered = async (messageIds: string[]) => {
  const response = await postRequest('/message/mark-delivered', { message_ids: messageIds }, false);
  if (response.status === 200) {
    messageIds.forEach(messageId => {
      const dateKey = Object.keys(messages.value).find(date =>
          messages.value[date].some((m: Message) => m.id === messageId)
        );

        if(dateKey) {
            const message = messages.value[dateKey].find((m: Message) => m.id === messageId);

        if (message) {
            message.is_delivered = true;
            message.delivered_at = new Date().toISOString()
        }
      }
      });
  }
}

function handleItemClick(item: { title: string; value: string }, message: Message) {
  if (item.value === 'edit') {
    editMessageId.value = message.id
    newMessage.value = message.message
    nextTick(() => {
      const messageInput = document.querySelector('input[type="text"]') as HTMLInputElement
      messageInput?.focus()
    })
  } else if (item.value === 'delete') {
    deleteMessage(message.id)
  }
}

const checkScreenSize = () => {
  isMobile.value = window.innerWidth <= 992;
};

watch(() => newMessage.value, (newVal) => {
  if (newVal) {
    handleTyping()
  }
})

watch(search, () => {
  fetchUsers()
});

watch(sidebarActiveTab,() => {
  isGroupMessage.value = false
  closeChat()
})

function handleMessageEvent(message:Message,type:string) {
  switch (type) {
    case 'sent':
        // Get today's date as key
      const today = new Date().toLocaleDateString('en-GB', { 
        day: 'numeric',
        month: 'short', 
        year: 'numeric'
      }).replace(',', ''); // Remove the comma if needed
      
      if (!messages.value[today]) {
        messages.value[today] = [];
      }
      messages.value[today].push(message);
      childRef?.value?.showNewMessage(); // Call the child function to show lates message 
      updateMessageStatus(message, 'seen');
      // Update last message for selected user
      if (selectedUser.value) {
        selectedUser.value.last_message = {
          message: message.message,
          created_at: message.created_at,
          attachments: message.attachments
        };
      }
      break;
    case 'updated':
    const dateKey = Object.keys(messages.value).find(date =>
      messages.value[date].some((m: Message) => m.id === message.id)
    );

    if(dateKey) {
        const index = messages.value[dateKey].findIndex((m: Message) => m.id === message.id);
        if (index !== -1) {
          messages.value[dateKey][index] = message;
          // Update last message if needed
          if (index === messages.value[dateKey].length - 1 && selectedUser.value) {
            selectedUser.value.last_message = {
              message: message.message,
              created_at: message.created_at,
              attachments: message.attachments
            };
          }
        }
      }
      break;
    case 'deleted':
      for (const [date, messageGroup] of Object.entries(messages.value)) {
        const index = messageGroup.findIndex((m: Message) => m.id === message.id);
        if (index !== -1) {
          messages.value[date].splice(index, 1);
          if (messages.value[date].length === 0) {
            delete messages.value[date];
          }
          break;
        }
      }
      break;
  }
}

onMounted(async () => {
  await fetchUsers();
  await fetchGroups()
  checkScreenSize();
  window.addEventListener("resize", checkScreenSize);

  // Testing code for typing event listen for group 
  // const interval = setInterval(() => {
  //   newMessage.value = `Updated at ${new Date().toLocaleTimeString()}`;
  // }, 2000); // Updates every 2 seconds

  // onUnmounted(() => {
  //   clearInterval(interval); // Cleanup to prevent memory leaks
  // });

  echo.private(`chat.${loggedInUser.value?.id}`)
    .listen('.MessageEvent', (e: { message: Message; type: string }) => {
      // Only process messages from selected user
      // If message is from a different user than currently selected
      if (e.message.sender_id !== selectedUser.value?.id && e.type === 'sent') {
        // Try to find user in chat users list
        let user = chatUsers.value.find((u: User) => u.id === e.message.sender_id);
        if (!user) {
          // If not found in chat users, look in contact users
          const contactUser = contactUsers.value.find((u: User) => u.id === e.message.sender_id);
          if (contactUser) {
            // Move user from contacts to chat users
            chatUsers.value.push(contactUser);
            contactUsers.value = contactUsers.value.filter(u => u.id !== contactUser.id);
            user = contactUser;
          }
        }
        // Update the last message for this user
        if (user) {
          user.last_message = {
            message: e.message.message,
            created_at: e.message.created_at,
            attachments: e.message.attachments,
            unread_messages: (user.last_message?.unread_messages || 0) + 1
          };
        }

        // Mark message as delivered and exit
        markAsDelivered([e.message.id]);
        return;
      }

      // Handle different message events
      handleMessageEvent(e.message,e.type);
    })
    // Listen for message status updates (delivered/seen)
    .listen('.MessageStatusEvent', (e: { messageIds: string[]; status: string; user_id: string }) => {
      const timestamp = new Date().toISOString();
      
      e.messageIds.forEach(messageId => {
        const dateKey = Object.keys(messages.value).find(date =>
          messages.value[date].some((m: Message) => m.id === messageId)
        );

        if(dateKey) {
        const message = messages.value[dateKey].find((m: Message) => m.id === messageId);
        if (!message) return;

        if (e.status === 'delivered') {
          message.is_delivered = true;
          message.delivered_at = timestamp;
        } else if (e.status === 'seen') {
          message.is_seen = true; 
          message.seen_at = timestamp;
        }
      }
      });
    })
    // Listen for typing indicator events
    .listenForWhisper('typing', (e: { user: User }) => {
      if (selectedUser.value?.id !== e.user.id) return;
      
      isTyping.value = true;
      if (typingTimeout.value) clearTimeout(typingTimeout.value);
      
      // Reset typing indicator after 3 seconds of no typing
      typingTimeout.value = setTimeout(() => {
        isTyping.value = false;
      }, 3000);
    })
    // Listen for stop typing events
    .listenForWhisper('stopTyping', (e: { user: User }) => {
      if (selectedUser.value?.id === e.user.id) {
        isTyping.value = false
      }
    }).listenForWhisper('messageStatus', (e: { message: Message; status: string; user_id: string }) => {
      if (selectedUser.value?.id === e.user_id) {
        const dateKey = Object.keys(messages.value).find(date =>
          messages.value[date].some((m: Message) => m.id === e.message.id)
        );

        if(dateKey) {
            const message = messages.value[dateKey].find((m: Message) => m.id === e.message.id);
          if (message) {
            message.is_seen = e.status === 'seen';
            message.is_delivered = e.status === 'delivered';
            message.seen_at = e.status === 'seen' ? new Date().toISOString() : undefined;
            message.delivered_at = e.status === 'delivered' ? new Date().toISOString() : undefined;
          }
        }
      }
  });

  echo.join('presence.chat')
    // List all users currently online when joining the channel
    .here((users: User[]) => {
      users.forEach((user: User) => {
        // Mark users as online
        const chatUser = chatUsers.value.find((u: User) => u.id === user.id);
        if (chatUser) {
          chatUser.is_online = true;
        }
      });
    })
    // Listen for users joining the channel
    .joining((user: User) => {
      // Mark user as online
      const chatUser = chatUsers.value.find((u: User) => u.id === user.id);
      if (chatUser) {
        chatUser.is_online = true;
      }
    })
    // Listen for users leaving the channel
    .leaving((user: User) => {
      // Mark user as offline
      const chatUser = chatUsers.value.find((u: User) => u.id === user.id);
      if (chatUser) {
        chatUser.is_online = false;
      }
  });

  if (groups.value.length > 0) {
    echo.private('group.messages')
      .listen('.GroupMessageEvent', (e: any) => {
        // Find the target group
        let targetGroup = groups.value.find((g: Group) => g.id === e.message.group_id);
        if (!targetGroup) return;

        if (e.message.group_id !== selectedUser.value?.id && e.type === 'sent') {
          // Update the last message for the corresponding group
          targetGroup.last_message = {
            message: e.message.message,
            created_at: e.message.created_at,
            attachments: e.message.attachments,
          };
          return;
        }
       if(e.message.sender_id !== loggedInUser.value?.id) {
        handleMessageEvent(e.message, e.type);
       }
      }).listenForWhisper('typing', (e: { user: User }) => {
        if (loggedInUser.value?.id === e.user.id) return;

        isTyping.value = true;
        const typingUsers = typingGroupMember.value?.split(', ');
        if (typingUsers && !typingUsers.includes(e.user.first_name)) {
          typingUsers.push(e.user.first_name); // Add the first_name of the typing user
          typingGroupMember.value = typingUsers.join(', ');
        }
        if (typingTimeout.value) clearTimeout(typingTimeout.value);
        
        // Reset typing indicator after 3 seconds of no typing
        typingTimeout.value = setTimeout(() => {
          isTyping.value = false;
          typingGroupMember.value = ''; // Pop the first_name of the typing user
        }, 3000);
    })
    // Listen for stop typing events
    .listenForWhisper('stopTyping', (e: { user: User }) => {
      if (loggedInUser.value?.id !== e.user.id) {
        let typingUsers = typingGroupMember.value?.split(', ');
        const index = typingUsers ? typingUsers?.indexOf(e.user.first_name) : -1;
        if (index !== -1) {
          typingUsers.splice(index, 1);
          typingGroupMember.value = typingUsers.join(', '); // Remove the first_name of the user who stopped typing
        }
        // if (typingUsers.length === 0) {
          isTyping.value = false;
          typingGroupMember.value = ''; // Pop the first_name of the typing user
        // }
      }
    });
  }
});


onBeforeUnmount(() => {
  window.removeEventListener("resize", checkScreenSize);
});
</script>

<template>
  <v-container class="fill-height pa-0">
    <v-row no-gutters>
      <!-- Users List -->
      <v-col cols="12" md="3" :class="{'user-list':isMobile && isChatOpen}">
        <v-card>
          <v-card-title class="px-4">
            <!-- <span class="text-h6">Messages</span> -->
            <v-text-field v-model="search" placeholder="Search..." class="mt-2"></v-text-field>
            <div class="d-flex justify-space-between">
              <VTabs v-model="sidebarActiveTab" class="v-tabs--grow">
                <VTab v-for="item in tabs" :key="item.icon" :value="item.tab">
                  <VIcon size="20" start :icon="item.icon" />
                  {{ item.title }}
                </VTab>
              </VTabs>
            </div>

          </v-card-title>

          <v-list class="messages-container">
            <div>
              <VWindow v-model="sidebarActiveTab" class="disable-tab-transition" :touch="false">
                  <!-- User -->
                <VWindowItem value="user">
                  <!-- Chat Users Section -->
                  <div class="pa-3" v-if="chatUsers?.length">
                    <div class="d-flex justify-space-between align-center mb-2">
                      <span class="text-h6">Chat Users</span>
                      <v-chip color="primary" size="small">{{ chatUsersCount }}</v-chip>
                    </div>
                        <UserListItem
                          v-for="user in chatUsers"
                          :key="user.id"
                          :user="user"
                          :selectedUser="selectedUser"
                          :isChatUser="true"
                          @select-user="selectUser"
                        />
                  </div>

                  <!-- Contact Users Section -->
                  <div class="pa-3" v-if="contactUsers?.length">
                    <div class="d-flex justify-space-between align-center mb-2">
                      <span class="text-h6">Contacts</span>
                      <v-chip color="secondary" size="small">{{ contactUsersCount }}</v-chip>
                    </div>
                      <UserListItem
                        v-for="user in contactUsers"
                        :key="user.id"
                        :user="user"
                        :selectedUser="selectedUser"
                        @select-user="selectUser"
                      />
                  </div>
                </VWindowItem>

                <!-- Group -->
                <VWindowItem value="group">
                     <!-- Group Section -->
                  <div class="pa-3" v-if="chatUsers?.length">
                    <div class="d-flex justify-space-between align-center mb-2">
                      <span class="text-h6">Group</span>
                      <v-chip color="primary" size="small">{{ groupsCount }}</v-chip>
                    </div>
                        <GroupList
                          v-for="group in groups"
                          :key="group.id"
                          :group="group"
                          :selectedGroup="selectedUser"
                          :isChatUser="true"
                          @select-group="selectGroup"
                        />
                  </div>
                </VWindowItem>
              </VWindow>
            </div>
          </v-list>
        </v-card>
      </v-col>

      <!-- Chat Area -->
      <v-col cols="12" md="9" :class="{'user-list':isMobile && !isChatOpen}">
        <v-card>
          <template v-if="selectedUser">
            <!-- Chat Header -->
            <v-card-title class="py-4 px-4 border-b d-flex justify-space-between mb-1 card-header-tabs">
         
             <div class="d-flex align-center" v-if="!isGroupMessage">
                <VBadge
                  dot
                  location="bottom right"
                  offset-x="3"
                  offset-y="3"
                  bordered
                  :color="selectedUser.is_online ? 'success' : 'secondary'">
                    <v-avatar size="40" class="mr-3">
                      <v-img v-if="selectedUser.profile_image" :src="selectedUser.profile_image" :alt="selectedUser.first_name" />
                      <span v-else>{{ avatarText(selectedUser.full_name) }}</span>
                    </v-avatar>
                </VBadge>
              <div>
                <div class="text-h6">{{ selectedUser.first_name }} {{ selectedUser.last_name }}</div>
                <div class="text-subtitle-2">{{ selectedUser.email }}</div>
              </div>
             </div>

             <div class="align-center d-flex" v-else>
              <v-avatar size="40" class="mr-3">
                <v-img v-if="selectedUser.image" :src="selectedUser.image" :alt="selectedUser.first_name" />
                <span v-else >{{ avatarText(selectedUser.name) }}</span>
              </v-avatar>
              <div>
                <div class="text-h6">{{ selectedUser.name }}</div>
                <div class="text-sm">Member {{ selectedUser.group_members }}</div>
              </div>
             </div>

                 <!-- Back Button for Mobile -->
            <v-btn class="back-button" icon v-if="isMobile" @click="closeChat">
              <v-icon>mdi-arrow-left</v-icon>
            </v-btn>
            </v-card-title>

            <!-- Messages Area -->
            <MessageList
              ref="childRef"
              :messages="messages"
              :selectedUser="selectedUser"
              :loggedInUser="loggedInUser"
              :loading="loading"
              :isLoadingMore="isLoadingMore"
              :target="target"
              :distance="distance"
              :identifier="resetData"
              :isGroupMessage="isGroupMessage"
              @infinite="loadMoreMessages"
              @deleteAttachment="deleteMessageAttachment"
              @itemClick="handleItemClick"
            />

            <!-- Message Input -->
            <v-card-actions class="pa-4 border-t">
              <v-form @submit.prevent="editMessageId ? updateMessage() : sendMessage()" class="w-100" v-show="!isRecording">
                <v-row align="center" no-gutters>
                  <v-col cols="auto">
                    <VueDropzone id="dropzone" ref="fileInput" :options="dropzoneOptions"
                      @vdropzone-success="onFileAdded" class="d-none" />
                    <v-btn icon variant="text" @click="fileInput.$el.click()">
                      <v-icon>mdi-paperclip</v-icon>
                    </v-btn>
                  </v-col>

                  <v-col class="px-2">
                    <v-text-field v-model="newMessage" placeholder="Type a message..." variant="outlined"
                      density="compact" hide-details />
                    <small v-if="isTyping && !isGroupMessage" class="text-gray-700">
                      {{ selectedUser.first_name }} is typing...
                    </small>
                    <small v-else-if="isTyping && isGroupMessage">
                      {{ typingGroupMember }} is typing
                    </small>
                  </v-col>

                  <v-col cols="auto d-flex align-center gap-2">
                    <v-btn v-if="!editMessageId" color="primary" icon @click="sendMessage">
                      <v-icon>mdi-send</v-icon>
                    </v-btn>
                    <v-btn v-else color="primary" icon @click="updateMessage">
                      <v-icon>mdi-update</v-icon>
                    </v-btn>
                  </v-col>
                </v-row>
              </v-form>
              <AudioRecorder @recordingComplete="onAudioRecordingComplete" @recording-start="isRecording = true"
                @recording-stop="isRecording = false" />
            </v-card-actions>
          </template>

          <!-- show when no any chat open -->
          <v-card-text v-else class="d-flex flex-column align-center justify-center fill-height text-center">
            <!-- SVG Icon -->
            <svg width="300" class="no-chat mb-3" enable-background="new 0 0 64 64" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
              <circle cx="32" cy="32" fill="#77b3d4" r="32"/>
              <path d="m52 32c0-9.9-9-18-20-18s-20 8.1-20 18c0 9.6 8.3 17.4 18.8 17.9.7 3.7 1.2 6.1 1.2 6.1s5-3 9.6-8.2c6.2-3.1 10.4-9 10.4-15.8z" fill="#231f20" opacity=".2"/>
              <path d="m49 28.8c0 15-17 25.2-17 25.2s-9.4-42 0-42 17 7.5 17 16.8z" fill="#fff"/>
              <ellipse cx="32" cy="30" fill="#fff" rx="20" ry="18"/>
              <g fill="#4f5d73">
                <circle cx="32" cy="30" r="2"/>
                <circle cx="40" cy="30" r="2"/>
                <circle cx="24" cy="30" r="2"/>
              </g>
            </svg>

            <!-- Bottom Text -->
            <p class="mt-3 text-secondary">Start Conversation</p>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>

  <FilePreview v-if="showFilePreview" :show-modal="showFilePreview" :files="attachments" :loading="isSendingMessageLoading" v-model:message="newMessage"
    :show-message-input="true" @addMoreImage="fileInput.$el.click()" @close-modal="closeFilePreview"
    @send="sendMessage" />
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

.message-status {
  display: inline-flex;
  align-items: center;
}

.hover-container {
  position: relative;
}

.hover-container .delete-icon {
  z-index: 1;
  display: none;
  inset-block-start: 4px;
  inset-inline-end: 4px;
}

.hover-container:hover .delete-icon {
  display: block;
}

.messages-container {
  overflow-y: auto;
  height: calc(100vh - 350px);

  &::-webkit-scrollbar {
    display: none; // Hide scrollbar
  }
}

.v-card-title {
  padding: 8px 16px;
}

.v-btn {
  min-width: 48px;
  height: 48px;
}

.v-text-field {
  font-size: 14px;
}
.back-button {
  text-transform: none;
  font-size: 16px;
  font-weight: 500;
  background-color: transparent !important;
  padding: 0;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 4px;
  transition: color 0.3s ease, background-color 0.3s ease;
}

.back-button:hover {
  color: var(--v-primary-darken2); /* Darker hover effect */
  background-color: var(--v-primary-lighten5); /* Light background on hover */
  border-radius: 8px;
}

.back-text {
  margin-left: 4px;
}

@media (max-width: 992px) {
  .user-list {
    display: none; /* Hide user list on larger screens */
  }
}
.no-chat {
  height: calc(100vh - 350px);
  transition: transform 0.3s ease-in-out;
    &:hover {
      transform: scale(1.2); /* Zoom in by 20% on hover */
    }
}

.card-header-tabs {
  background-color: #777286;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 10%);
}
</style>

