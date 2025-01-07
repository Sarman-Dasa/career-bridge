<script setup lang="ts">
import { avatarText } from "@/@core/utils/formatters";
import { computed } from "vue";

const props = defineProps({
  connectionsData: {
    type: Object as PropType<any>,
    required: true,
  },
  isSuggestConnectionData: {
    type: Boolean,
    default: false,
  },
  connectionType: {
    type: String,
    default: "sent",
  },
});

const emit = defineEmits(["sendRequest", "updateConnection","withdrawRequest"]);

const menuList = ref([
  { title: 'Share connection', value: 'Share connection' },
  { title: 'Block connection', value: 'Block connection' },
  { type: 'divider', class: 'my-2' },
  { title: 'withdraw', value: 'withdraw', class: 'text-error' },
]);

// Computed values for frequently used data
const profileImage = computed(() => props.connectionsData.profile_image);
const connectionStatus = computed(() => props.connectionsData.pivot?.status);
const isConnected = computed(() => connectionStatus.value === "A");
const connectionCount = computed(() => props.isSuggestConnectionData
  ? props.connectionsData.mutual_connections_count
  : props.connectionsData.connection_count);
const connectionLabel = computed(() => (props.isSuggestConnectionData ? "Mature Connections" : "Connections"));


function updateConnectionStatus(status: string, id: string) {
  const obj = {
    id,
    status
  }
  emit('updateConnection', obj);
}

// Handle item click event
const withdrawRequest = (id: string) => {
  emit('withdrawRequest',id);
};
</script>

<template>
  <VCard class="card-container">
    <div class="vertical-more">
      <VBtn v-if="!props.isSuggestConnectionData && !isConnected" variant="none" class="me-4" size="34" prepend-icon="mdi-account-remove"
          @click="withdrawRequest(props.connectionsData.id)">
          <VTooltip activator="parent" location="top">withdraw request</VTooltip>
        </VBtn>
    </div>

    <VCardItem class="flex-grow">
      <VCardTitle class="d-flex flex-column align-center justify-center">
        <VAvatar size="100" :image="profileImage" :variant="!profileImage ? 'tonal' : 'elevated'">
          <span v-if="!profileImage">{{ avatarText(props.connectionsData.full_name) }}</span>
        </VAvatar>

        <p class="mt-4 mb-0">{{ props.connectionsData.full_name }}</p>
        <span class="text-body-1">{{ props.connectionsData.designation }}</span>

        <div class="d-flex align-center flex-wrap gap-2 mt-2">
          <VChip v-for="chip in props.connectionsData.chips" :key="chip.title" label :color="chip.color" size="small">
            {{ chip.title }}
          </VChip>
        </div>
      </VCardTitle>
    </VCardItem>

    <VCardText class="flex-grow">
      <div class="d-flex justify-space-around">
        <div class="text-center" v-if="connectionCount">
          <h6 class="text-h6">{{ connectionCount }}</h6>
          <span class="text-body-1">{{ connectionLabel }}</span>
        </div>
      </div>
    </VCardText>

    <div class="button-container">
      <div class="d-flex justify-center gap-4" v-if="!props.isSuggestConnectionData">
        <VBtn v-if="props.connectionType !== 'receive'"
          :prepend-icon="isConnected ? 'tabler-user-check' : 'tabler-clock'"
          :variant="isConnected ? 'elevated' : 'tonal'">
          {{ isConnected ? "connected" : "pending" }}
        </VBtn>

        <div v-else>
          <div v-if="!isConnected">
            <VBtn prepend-icon="mdi-check" :variant="'outlined'" class="mr-2"
              @click="updateConnectionStatus('A', props.connectionsData.pivot.user_id)">
              Accept
            </VBtn>
            <VBtn prepend-icon="mdi-close" :variant="'outlined'"
              @click="updateConnectionStatus('R', props.connectionsData.pivot.user_id)">
              Ignore
            </VBtn>
          </div>
          <div v-else>
            <VBtn prepend-icon="tabler-user-check" variant="elevated">
              connected
            </VBtn>
          </div>
        </div>

        <IconBtn v-if="isConnected" variant="tonal" class="rounded">
          <VIcon icon="tabler-mail" />
        </IconBtn>
      </div>

      <div class="d-flex justify-center gap-4 mt-2" v-else>
        <VBtn prepend-icon="tabler-user-plus" variant="tonal" @click="emit('sendRequest', props.connectionsData.id)">
          connect
        </VBtn>

        <IconBtn v-if="isConnected" variant="tonal" class="rounded">
          <VIcon icon="tabler-mail" />
        </IconBtn>
      </div>
    </div>
  </VCard>
</template>

<style lang="scss" scoped>
.card-container {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-block-size: 320px;

  /* Adjust the min height as needed */
}

.flex-grow {
  flex-grow: 1;
}

.button-container {
  display: flex;
  justify-content: center;
  padding-block: 1rem;
}
</style>
