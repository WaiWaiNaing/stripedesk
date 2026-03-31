import { reactive } from 'vue';

const state = reactive({
  items: [],
});

let nextId = 1;

export function useToast() {
  function push(message, type = 'info', ttlMs = 3000) {
    const id = nextId++;
    state.items.push({ id, message, type });
    window.setTimeout(() => dismiss(id), ttlMs);
  }

  function dismiss(id) {
    const idx = state.items.findIndex((x) => x.id === id);
    if (idx >= 0) state.items.splice(idx, 1);
  }

  return { toasts: state, push, dismiss };
}

