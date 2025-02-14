/* eslint-disable import/order */
import '@/@iconify/icons-bundle'
import App from '@/App.vue'
import layoutsPlugin from '@/plugins/layouts'
import vuetify from '@/plugins/vuetify'
import { loadFonts } from '@/plugins/webfontloader'
import router from '@/router'
import { postRequest } from '@/services/apiService'
import '@core-scss/template/index.scss'
import '@styles/styles.scss'
import { createPinia } from 'pinia'
import InfiniteLoading from "v3-infinite-loading"
import "v3-infinite-loading/lib/style.css"
import { createApp } from 'vue'
import { useUserStore } from './pages/user-profile/useUserStore'
import './plugins/echo'; // Import Echo configuration
import { formatDate } from './utils/dateFormatter'

loadFonts()


// Create vue app
const app = createApp(App)

// Add the date formatter as a global property
app.config.globalProperties.$formatDate = formatDate;

// Use plugins
app.use(vuetify)
app.use(createPinia())
app.use(router)
app.use(layoutsPlugin)
app.component("infinite-loading", InfiniteLoading);


if(localStorage.getItem('userData') && localStorage.getItem('accessToken')) {
  const userStore = useUserStore();

  let userData = JSON.parse(localStorage.getItem('userData'));
  userStore.setUser(userData);

  // Mark all messages as delivered when app opens
  // Call mark-all-delivered API
  postRequest('/message/mark-all-delivered', {}, false)
    .catch(error => console.error('Error marking messages as delivered:', error));
}


// Mount vue app
app.mount('#app')
