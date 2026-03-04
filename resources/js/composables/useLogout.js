import { useRouter } from 'vue-router';
import axios from 'axios';
import { setStoredToken, getStoredToken } from '../router';

export function useLogout() {
  const router = useRouter();

  async function logout() {
    const token = getStoredToken();
    if (token) {
      try {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        await axios.post('/api/logout');
      } catch (_) {}
      setStoredToken(null);
    }
    router.push('/login');
  }

  return { logout };
}

