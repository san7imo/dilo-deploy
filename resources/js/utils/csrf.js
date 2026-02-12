const readCookieValue = (name) => {
  if (typeof document === "undefined") return "";
  const escaped = name.replace(/[-/\\^$*+?.()|[\]{}]/g, "\\$&");
  const match = document.cookie.match(new RegExp(`(?:^|; )${escaped}=([^;]*)`));
  return match ? decodeURIComponent(match[1]) : "";
};

export const getXsrfToken = () => readCookieValue("XSRF-TOKEN");

export const xsrfHeader = () => {
  const token = getXsrfToken();
  return token ? { "X-XSRF-TOKEN": token } : {};
};
