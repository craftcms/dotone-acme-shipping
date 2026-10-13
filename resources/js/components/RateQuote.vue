<script setup lang="ts">
  const props = withDefaults(defineProps<{weight?: number}>(), {weight: 1});

  const rate = (weight: number) => (8.5 + weight * 1.75).toFixed(2);
</script>

<template>
  <craft-pane>
    <h3 class="font-bold text-sm mb-2">Rate quote</h3>
    <p class="text-sm">
      {{ props.weight }} kg &mdash; <strong>${{ rate(props.weight) }}</strong>
    </p>
  </craft-pane>
</template>
