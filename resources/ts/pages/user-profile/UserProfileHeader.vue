<script lang="ts" setup>
import { avatarText } from "@/@core/utils/formatters";
import coverImg from "@images/user-profile-header-bg.png";
import { useUserStore } from './useUserStore';

const userStore = useUserStore();

const profileHeaderData = computed(() => {
  return userStore.user
})

// watch(user, (newVal) => {
//   console.log(newVal);
//   profileHeaderData.value = newVal;
// }, {
//   immediate: true,
//   deep: true
// })

function getProfileImageUrl(imagePath:string) {
    if (!imagePath) return null;
    const baseUrl = import.meta.env.VITE_API_IMAGE_PATH;
    console.log(`${baseUrl}${imagePath}`);
    return `${baseUrl}${imagePath}`;
  }

</script>

<template>
  <VCard v-if="profileHeaderData">
    <VImg
      :src="coverImg"
      min-height="125"
      max-height="250"
      cover
    />

    <VCardText class="d-flex align-bottom flex-sm-row flex-column justify-center gap-x-5">
      <div class="d-flex h-0">
        <VAvatar
          v-if="profileHeaderData?.profile_image"
          rounded
          size="120"
          :image="profileHeaderData?.profile_image"
          class="user-profile-avatar mx-auto"
          :variant="!profileHeaderData?.profile_image ? 'tonal' : 'elevated'"
        />
        <span v-else>{{ avatarText(profileHeaderData?.full_name) }}</span>
      </div>

      <div class="user-profile-info w-100 mt-16 pt-6 pt-sm-0 mt-sm-0">
        <h6 class="text-h6 text-center text-sm-start font-weight-medium mb-3">
          {{ profileHeaderData?.full_name }}
        </h6>

        <div class="d-flex align-center justify-center justify-sm-space-between flex-wrap gap-4">
          <div class="d-flex flex-wrap justify-center justify-sm-start flex-grow-1 gap-2">
            <span class="d-flex">
              <VIcon
                size="20"
                icon="tabler-color-swatch"
                class="me-1"
              />
              <span class="text-body-1">
                {{ profileHeaderData?.role_name }}
              </span>
            </span>

            <span class="d-flex align-center">
              <VIcon
                size="20"
                icon="tabler-map-pin"
                class="me-2"
              />
              <span class="text-body-1">
                {{ profileHeaderData?.city }}
              </span>
            </span>

            <span class="d-flex align-center">
              <VIcon
                size="20"
                icon="tabler-calendar"
                class="me-2"
              />
              <span class="text-body-1">
                {{ $formatDate(profileHeaderData?.created_at) }}
              </span>
            </span>
          </div>

          <VBtn prepend-icon="tabler-check">
            Connected
          </VBtn>
        </div>
      </div>
    </VCardText>
  </VCard>
</template>

<style lang="scss">
.user-profile-avatar {
  border: 5px solid rgb(var(--v-theme-surface));
  background-color: rgb(var(--v-theme-surface)) !important;
  inset-block-start: -3rem;

  .v-img__img {
    border-radius: 0.125rem;
  }
}
</style>
