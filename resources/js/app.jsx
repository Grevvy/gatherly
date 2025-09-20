import React from "react"
import './app' 
import { createRoot } from "react-dom/client"
import "../css/app.css";

// Example component
function Hello({ name }) {
  return <h2 className="text-xl">Hello, {name}!</h2>
}

// Mount all React islands
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("[data-react-component]").forEach(node => {
    const name = node.getAttribute("data-react-component")
    const props = JSON.parse(node.getAttribute("data-props") || "{}")
    const root = createRoot(node)

    const registry = { Hello } // register your React components here
    if (registry[name]) root.render(React.createElement(registry[name], props))
  })
})
