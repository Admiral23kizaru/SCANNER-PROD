/**
 * @fileoverview useLogout.js
 * 
 * Source: AdminLayout.vue, TeacherDashboard.vue (Log out buttons)
 * Destination: POST /api/logout
 * Function: Revokes the current access token and clears local storage to log the user out.
 */

import { useRouter } from 'vue-router';
import { logoutUser } from '../services/authService';
import { setStoredToken } from '../router';

/**
 * useLogout Composable
 * 
 * Source: UI Navigation bar dropdowns and sidebar footer.
 * Destination: Vue Composition API state and Vue Router.
 * Function: Provides a reusable logout method for all dashboards.
 * 
 * @returns {Object} An object containing the logout function.
 */
export function useLogout() {
  const router = useRouter();

  /**
   * Logs out the user securely by calling the API and clearing tokens.
   * 
   * Source: Click event on Log out button.
   * Destination: authService.js -> logoutUser() -> /api/logout
   * Function: Informs the Laravel backend to revoke the token, then clears it locally.
   * 
   * Security Note: We clear the token immediately from localStorage to prevent 
   * session hijacking or replay attacks (Cybersecurity best practice). 
   * Even if the API call fails, the client is securely logged out locally.
   * 
   * @returns {Promise<void>}
   */
  async function logout() {
    try {
      // Data Flow: Wait for Axios POST /api/logout to gracefully invalidate Sanctum token
      await logoutUser();
    } catch (_) {
      // Silent catch: Ensure logout proceeds locally even if offline/network error
    } finally {
      // Data Flow: Always remove JWT/Bearer token from localStorage
      setStoredToken(null);
      // Redirect to login form
      router.push('/login');
    }
  }

  return { logout };
}
