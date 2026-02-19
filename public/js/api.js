const API = {
  async getSettings() {
    const res = await fetch('/api/settings.php');
    if (!res.ok) throw new Error('Failed to load settings');
    return (await res.json()).data;
  },

  async getBranches() {
    const res = await fetch('/api/branches.php');
    if (!res.ok) throw new Error('Failed to load branches');
    return (await res.json()).data;
  },

  async getCommittees() {
    const res = await fetch('/api/committees.php');
    if (!res.ok) throw new Error('Failed to load committees');
    return (await res.json()).data;
  },

  async getMedia() {
    const res = await fetch('/api/media.php');
    if (!res.ok) throw new Error('Failed to load media');
    return (await res.json()).data;
  },

  async getEvents() {
    const res = await fetch('/api/events.php');
    if (!res.ok) throw new Error('Failed to load events');
    return (await res.json()).data;
  }
};
