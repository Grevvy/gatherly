function initPasswordToggle() {
  const passwordField = document.getElementById("password")
  const toggleBtn = document.getElementById("togglePassword")
  const iconEye = document.getElementById("iconEye")
  const iconEyeOff = document.getElementById("iconEyeOff")

  if (!passwordField || !toggleBtn || !iconEye || !iconEyeOff) return

  toggleBtn.addEventListener("click", () => {
    const showing = passwordField.type === "password"
    passwordField.type = showing ? "text" : "password"
    iconEye.classList.toggle("hidden", !showing)    
    iconEyeOff.classList.toggle("hidden", showing)   
    passwordField.focus()
  })
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initPasswordToggle)
} else {
  initPasswordToggle()
}

