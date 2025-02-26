<script lang="ts" setup>
import MemberList from "@/components/chat/UserList.vue";
import { useDebouncedRef } from "@/ref/debouncedRef";
import { deleteRequest, getRequest, postRequest } from "@/services/apiService";
import { User } from "@/types";


const props = defineProps<{
  groupId: string;
  loggedInUser: User;
}>();

interface Emit {
  (e: "closeDetail"): void;
}

const emit = defineEmits<Emit>();

const groupDetail = ref([]);
const isConfirmDialogVisible = ref(false);
const id = ref<string | null>(null);
const action = ref<string | null>(null);
const showSuccessDialog = ref(false);
const isAddNewMembersPage = ref(false);
const userList = ref([]);
const selectedMember = ref([]);
const search = useDebouncedRef(null,500);

const actionButtonShow = computed(() => {
  return (
    props.loggedInUser?.id === groupDetail.value?.creator?.id ||
    (groupDetail.value?.members &&
      groupDetail.value.members.some(
        (member: any) =>
          member.user_id === props.loggedInUser?.id && member.role === "admin"
      ))
  );
});

watch(search,() => {
  getUserList()
})

async function getGroupDetail() {
  let response = await getRequest(`/group/view/${props?.groupId}`);
  if (response && response.status === 200) {
    console.log("response", response.data);
    let data = response.data;
    groupDetail.value = data.group;
  }
}

function handleActionResponse(object: { action: string; id: string }) {
  id.value = object.id;
  action.value = object.action;
  if (action.value === "delete") isConfirmDialogVisible.value = true;
}

async function dialogResult(result: boolean) {
  if (result) {
    const response = await deleteRequest(
      `/group/${props.groupId}/members/${id.value}`,
      {}
    );
    if (response && response.status == 200) {
      showSuccessDialog.value = true;
      getGroupDetail();
    }
  }
}

async function getUserList() {
  let input = {
    search: search.value,
    per_page: 25,
    page: 1,
  };
  let response = await postRequest(
    "/connection/connected-user-list",
    input,
    false
  );
  if (response && response.status === 200) {
    console.log("response", response.data);
    let data = response.data;
    userList.value = data.connections;
  }
  isAddNewMembersPage.value = true;
}

function handleSelectedUser(user:any) {
  if (selectedMember.value.includes(user.id)) {
    selectedMember.value = selectedMember.value.filter(id => id !== user.id);
  } else {
    selectedMember.value.push(user.id);
  }
  console.log("user",selectedMember.value);
}

async function addMembers() {
  let input = {
    members:selectedMember.value
  };
  // group/:id/members
  let response = await postRequest(
    `/group/${props.groupId}/members`,
    input,
    false
  );
  if (response && response.status === 200) {  
    
  }
  isAddNewMembersPage.value = false;

}

onMounted(() => {
  console.log("group id", props.groupId);
  getGroupDetail();
});
</script>
<template>
  <div class="group_detail">
    <div v-if="!isAddNewMembersPage">
      <div class="header">
        <!-- Group image -->
        <div class="group_image">
          <img :src="groupDetail?.image" alt="" />
        </div>
        <!-- Group name -->
        <div class="group_name">
          <h1>{{ groupDetail?.name }}</h1>
          <h2>
            Members :
            <v-badge
              :content="groupDetail?.members?.length"
              color="success"
              inline
            ></v-badge>
          </h2>
        </div>
        <v-btn class="back_btn" icon @click="emit('closeDetail')">
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
      </div>

      <v-card>
        <v-text-title>
          <p>Create By</p>
        </v-text-title>
        <v-card-text class="created_by">
          <v-avatar size="40">
            <v-img
              v-if="groupDetail?.creator?.profile_image"
              :src="groupDetail?.creator?.profile_image"
              :alt="groupDetail.creator.first_name"
            />
            <span v-else>{{ avatarText }}</span>
          </v-avatar>
          <div class="name_role">
            <p>{{ groupDetail?.creator?.full_name }}</p>
            <v-badge :content="'admin'" color="success" inline></v-badge>
          </div>
        </v-card-text>
      </v-card>

      <div class="member_container">
        <p>{{ groupDetail?.members?.length }} Members</p>
        <div class="add_member_section" @click="getUserList">
          <v-icon icon="tabler-user-plus" />
          <span class="ml-2">Add Members</span>
        </div>
        <MemberList
          v-for="member in groupDetail.members"
          :key="member.id"
          :user="member.user"
          :role="member.role"
          :createdBy="member.creator"
          :actionButtonShow="actionButtonShow"
          @handleActionButton="handleActionResponse"
        />
      </div>
    </div>
    <div v-if="isAddNewMembersPage">
      <div class="d-flex justify-space-between">
        <v-btn class="member_back_btn" icon @click="isAddNewMembersPage = false">
          <v-icon>mdi-arrow-left</v-icon>
        </v-btn>
        <v-btn @click="addMembers">
          Add Members
          <template v-slot:append>
            <VIcon icon="tabler-user-plus"></VIcon>
          </template>
        </v-btn>
      </div>
      <v-text-field v-model="search" placeholder="Search ..." class="mt-2 mb-2"></v-text-field>
      <div v-if="selectedMember?.length" class="mb-2 mt-2">
        <span class="font-weight-bold">Selected Members:</span>
        <v-badge
            :content="selectedMember?.length"
            color="success"
            inline
        ></v-badge>
      </div>

      <MemberList
        v-for="user in userList"
        :key="user.id"
        :user="user"
        :selectedData="selectedMember"
        :showSelectedIcon="true"
        @selectUser="handleSelectedUser"
      />
    </div>
</div>

  <!-- 👉 Confirm Dialog -->
  <ConfirmDialog
    v-model:isDialogVisible="isConfirmDialogVisible"
    cancel-title="Cancelled"
    confirm-title="Member!"
    confirm-msg="Your group member has been successfully removed."
    confirmation-question="Are you sure you want to remove this group member?"
    cancel-msg="Cancelled!!"
    :showSuccessDialog="showSuccessDialog"
    @confirm="dialogResult"
    @closeSuccessDialog="showSuccessDialog = false"
  />
</template>

<style lang="scss" scoped>
.group_detail {
  padding: 10px 20px;
  overflow-y: auto;
  height: calc(100vh - 250px);
  position: relative;

  &::-webkit-scrollbar {
    display: none; // Hide scrollbar
  }

  .back_btn {
    position: absolute;
    left: 10px;
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

    &:hover {
      color: var(--v-primary-darken2);
      background-color: var(--v-primary-lighten5);
      border-radius: 100%;
    }
  }
  .member_back_btn {
    @extend .back_btn;
    position: static;
  }

  .header {
    display: flex;
    flex-direction: column;
    align-items: center;

    .group_image {
      border: 2px solid #000;
      border-radius: 100%;
      min-width: 150px;
      max-width: 150px;
      min-height: 150px;
      max-height: 150px;

      img {
        height: 100%;
        width: 100%;
        object-fit: cover;
        border-radius: 100%;
      }
    }

    .group_name {
      margin-top: 10px;
      text-align: center;

      h2 {
        display: flex;
        justify-content: center;
        align-items: center;
      }
    }
  }

  .created_by {
    display: flex;
    border: 2px solid;
    border-radius: 10px;
    padding: 10px 20px;

    .name_role {
      margin-left: 10px;

      p {
        margin: 0px;
      }
    }
  }

  .member_container {
    border: 2px solid;
    border-radius: 10px;
    margin-top: 10px;
    padding: 10px 10px;

    .add_member_section {
      margin-bottom: 20px;
      cursor: pointer;

      &:active {
        color: #0dfd;
      }
    }
  }
}
</style>
