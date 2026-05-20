import type { OfflineAction } from './types'

const STORAGE_KEY = 'hhcm-offline-queue'

export function loadOfflineQueue(): OfflineAction[] {
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY)
    if (!raw) {
      return []
    }

    return JSON.parse(raw) as OfflineAction[]
  } catch {
    return []
  }
}

export function saveOfflineQueue(queue: OfflineAction[]) {
  window.localStorage.setItem(STORAGE_KEY, JSON.stringify(queue))
}

export function addOfflineAction(action: OfflineAction): OfflineAction[] {
  const queue = [...loadOfflineQueue(), action]
  saveOfflineQueue(queue)
  return queue
}

export function removeOfflineAction(id: string): OfflineAction[] {
  const queue = loadOfflineQueue().filter((item) => item.id !== id)
  saveOfflineQueue(queue)
  return queue
}
