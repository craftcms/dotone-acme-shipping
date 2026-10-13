<script setup lang="ts">
  const props = defineProps<{
    payload: {id: number | null; title?: string};
    active: boolean;
    refreshToken: number;
  }>();

  const tracking = (id: number) => `ACME-${String(id).padStart(8, '0')}`;
</script>

<template>
  <!-- Panels own their padding; the details pane no longer adds any. -->
  <div class="px-(--cp-container-padding) py-4">
    <template v-if="props.payload.id !== null">
      <p class="text-sm mb-3">
        Tracking
        <code>{{ tracking(props.payload.id) }}</code>
      </p>
      <craft-status status="live" label="In transit"></craft-status>
    </template>
    <p v-else class="text-sm">Save the entry to open a shipment.</p>
  </div>
</template>
