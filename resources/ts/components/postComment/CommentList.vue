<script setup>
import { avatarText } from '@/@core/utils/formatters';

const props = defineProps({
  comments: {
    type: Array,
    required: true
  }
});

const emit = defineEmits(['like','reply']);

function like(commentId){
  emit('like',commentId);
}

function reply(comment){
  emit('reply',comment);
}

// Toggle the visibility of replies for each comment
const toggleReplies = (commentId) => {
  const comment = comments.value.find(c => c.id === commentId);
  comment.showReplies = !comment.showReplies;
};
</script>

<template>
  <v-container class="comment-list">
    <v-row>
        <v-col cols="12">
        <v-card v-for="comment in comments" :key="comment.id" class="mb-4" outlined>
          <!-- Parent Comment -->
          <v-card-title>
            <v-avatar size="40" class="mr-3">
              <v-img v-if="comment.user.profile_image" :src="comment.user.profile_image"></v-img>
              <span v-else>{{ avatarText(comment.user.full_name) }}</span>
            </v-avatar>
            <div class="d-flex flex-column">
              <span class="font-weight-bold">{{ comment.user.full_name }}</span>
              <span class="text-caption">{{  $formatDate(comment.created_at) }}</span>
            </div>
          </v-card-title>
          
          <v-card-subtitle>
            <span class="no-wrap">{{ comment.comment }}</span>
          </v-card-subtitle>
          <v-card-actions class="mt-4">
            <div>
              <v-btn text @click="like(comment.id)">
                <span class="me-2">{{ comment.has_like ? 'Liked' : 'Like' }} </span>
                <v-icon v-if="comment.has_like" icon="mdi-thumb-up" size="12" class="me-2"/> {{ comment.like_count }}
              </v-btn>
            </div> |
            <div>
              <v-btn text @click="reply(comment)">
                Reply {{ comment.children.length > 0 ? comment.children.length : '' }}
              </v-btn>
            </div>
          </v-card-actions>
          <div v-if="comment.children.length > 0" class="child-comments">
            <CommentList :comments="comment.children" @like="like" @reply="reply" />
          </div>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>



<style scoped lang="scss">
.comment-list {
  max-block-size: calc(100vh - 50px);
  overflow-y: auto;
  scrollbar-width: 0; // For Firefox

  // Scrollbar styles for Webkit-based browsers
  &::-webkit-scrollbar {
    inline-size: 0; // Scrollbar width
  }

  &::-webkit-scrollbar-track {
    border-radius: 10px;
    background: #f1f1f1;
  }

  &::-webkit-scrollbar-thumb {
    border: 2px solid #f1f1f1;
    border-radius: 10px;
    background-color: #525253;
  }

  &::-webkit-scrollbar-thumb:hover {
    background-color: #bdc1ca;
  }

  .child-comments {
    border-inline-start: 1px solid #e0e0e0; // Optional: add a visual indicator for nesting
    margin-block-start: 1rem;
    margin-inline-start: 2rem; // Indent child comments
    padding-inline-start: 1rem;

    .v-card {
      padding: 0;
      margin-block-end: 0;
    }

    .v-avatar {
      size: 32px;
    }

    .v-btn {
      font-size: 0.75rem;
    }

    .v-card--variant-elevated {
      border: none !important; /* Remove border for children */
      background-color: transparent !important; /* Transparent background for child comments */
      box-shadow: none !important;
      margin-inline-start: 0; /* Indent child comments */
    }
  }
}

.v-card {
  padding: 1rem;
  margin-block-end: 1rem;

  .v-card-title {
    display: flex;
    align-items: center;

    .v-avatar {
      background-color: #e0e0e0;
      margin-inline-end: 1rem;
    }

    .d-flex {
      flex-direction: column;

      span.font-weight-bold {
        font-size: 1rem;
      }

      span.text-caption {
        font-size: 0.875rem;
      }
    }
  }

  .v-card-subtitle {
    font-size: 0.95rem;
    margin-block-start: 0.5rem;

    .no-wrap {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  }

  .v-card-actions {
    margin-block-start: 1rem;

    .v-btn {
      font-size: 0.875rem;
      font-weight: 600;
      text-transform: capitalize;

      v-icon {
        margin-inline-start: 0.5rem;
      }

      &:hover {
        background-color: transparent;
      }
    }
  }
}

// Responsive design
@media (max-width: 768px) {
  .v-card {
    .v-card-title {
      align-items: flex-start;

      .v-avatar {
        margin-block-end: 0.5rem;
      }

      .d-flex {
        span.font-weight-bold {
          font-size: 0.9rem;
        }

        span.text-caption {
          font-size: 0.8rem;
        }
      }
    }

    .v-card-subtitle {
      font-size: 0.875rem;
    }

    .v-card-actions {
      .v-btn {
        margin-block-end: 0.5rem;
      }
    }
  }
}

@media (max-width: 480px) {
  .v-card {
    padding: 0.75rem;

    .v-card-title {
      .v-avatar {
        size: 32px;
      }

      .d-flex {
        span.font-weight-bold {
          font-size: 0.85rem;
        }

        span.text-caption {
          font-size: 0.75rem;
        }
      }
    }

    .v-card-subtitle {
      font-size: 0.8rem;
    }

    .v-card-actions {
      .v-btn {
        font-size: 0.8rem;
      }
    }
  }
}

</style>
