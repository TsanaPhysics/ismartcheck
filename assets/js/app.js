const API_URL = 'api';

async function apiCall(endpoint, action, method = 'GET', data = null) {
  const url = `${API_URL}/${endpoint}?action=${action}`;
  const options = {
    method,
    headers: { 'Content-Type': 'application/json' },
  };
  if (data) options.body = JSON.stringify(data);

  try {
    const res = await fetch(url, options);
    return await res.json();
  } catch (e) {
    console.error(e);
    return { success: false, message: 'Network error' };
  }
}

function showToast(message) {
  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.innerText = message;
  document.body.appendChild(toast);
  
  setTimeout(() => toast.classList.add('show'), 100);
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

function logout() {
  apiCall('auth.php', 'logout', 'GET').then(() => {
    window.location.href = 'index.html';
  });
}

async function checkAuth(autoRedirect = true) {
  const res = await apiCall('auth.php', 'me');
  const path = window.location.pathname;
  const isAuthPage = path.includes('login.html') || path.includes('index.html') || path.endsWith('/classcheck/');
  
  if (!res.success) {
    if (autoRedirect && !isAuthPage) {
      let targetUrl = 'login.html';
      if (path.includes('scan.html')) {
        targetUrl += '?redirect=' + encodeURIComponent('scan.html' + window.location.search);
      }
      window.location.href = targetUrl;
    }
  } else {
    const user = res.data;
    if (isAuthPage) {
      const urlParams = new URLSearchParams(window.location.search);
      const redirect = urlParams.get('redirect');
      if (redirect) {
         window.location.href = decodeURIComponent(redirect);
         return user;
      }
      if (user.role === 'superadmin') window.location.href = 'superadmin.html';
      else if (user.role === 'teacher') window.location.href = 'teacher.html';
      else window.location.href = 'student.html';
    }
    return user;
  }
  return null;
}

function checkLineBrowser() {
  if (navigator.userAgent.indexOf("Line") > -1) {
    const overlay = document.createElement('div');
    overlay.style.position = 'fixed';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.width = '100vw';
    overlay.style.height = '100vh';
    overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.95)';
    overlay.style.color = 'white';
    overlay.style.zIndex = '999999';
    overlay.style.display = 'flex';
    overlay.style.flexDirection = 'column';
    overlay.style.justifyContent = 'center';
    overlay.style.alignItems = 'center';
    overlay.style.padding = '20px';
    overlay.style.textAlign = 'center';
    overlay.innerHTML = `
      <h2 style="color: #00B900; margin-bottom: 20px;">⚠️ ตรวจพบการใช้งานผ่านแอป LINE</h2>
      <p style="font-size: 1.2rem; margin-bottom: 15px;">ระบบเช็คชื่อจะไม่สามารถทำงานได้สมบูรณ์ในแอป LINE</p>
      <p style="font-size: 1.2rem; margin-bottom: 30px;">กรุณากดที่ปุ่ม <b>จุด 3 จุด (⋮)</b> มุมขวาบน<br>แล้วเลือก <b>"เปิดในเบราว์เซอร์"</b> (Open in Browser) เพื่อเข้าสู่ระบบ</p>
      <div style="background: #333; padding: 15px; border-radius: 10px; width: 100%; max-width: 300px;">
         <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
           <span>1. แตะมุมขวาบน</span>
           <span>⋮</span>
         </div>
         <div style="display: flex; justify-content: space-between; color: #00B900; font-weight: bold;">
           <span>2. เลือก "เปิดในเบราว์เซอร์"</span>
           <span>↗️</span>
         </div>
      </div>
    `;
    document.body.appendChild(overlay);
    return true; // Is LINE
  }
  return false;
}
