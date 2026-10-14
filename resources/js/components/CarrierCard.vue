<script setup lang="ts">
  import {t} from '../t';
  import type {Quote} from '../composables/useRateQuotes';

  const props = defineProps<{quote: Quote; selected: boolean}>();
  defineEmits<{select: [handle: string]}>();

  const money = (value: number) =>
    new Intl.NumberFormat(undefined, {
      style: 'currency',
      currency: 'USD',
    }).format(value);
</script>

<template>
  <button
    type="button"
    class="carrier"
    :class="{'carrier--selected': props.selected}"
    :aria-pressed="props.selected"
    @click="$emit('select', props.quote.handle)"
  >
    <span class="carrier__head">
      <span class="carrier__name">{{ props.quote.name }}</span>
      <craft-badge v-if="props.quote.recommended" fill="teal">
        {{ t('Best value') }}
      </craft-badge>
    </span>

    <span class="carrier__rate">{{ money(props.quote.total) }}</span>

    <span class="carrier__meta">
      {{ props.quote.service }} &middot;
      {{ t('{days} day transit', {days: props.quote.etaDays}) }}
    </span>
  </button>
</template>

<style scoped>
  .carrier {
    display: grid;
    gap: var(--c-spacing-xs);
    width: 100%;
    text-align: start;
    padding: var(--c-spacing-lg);
    background: var(--c-surface-raised);
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-lg);
    cursor: pointer;
    font: inherit;
    color: inherit;
    transition: border-color 120ms ease, box-shadow 120ms ease;
  }

  .carrier:hover {
    box-shadow: var(--c-shadow-raised);
  }

  .carrier:focus-visible {
    outline: var(--c-focus-outline-width) var(--c-focus-outline-style)
      var(--c-color-focus-outline);
    outline-offset: var(--c-focus-outline-offset);
  }

  .carrier--selected {
    border-color: var(--c-color-accent-border-loud);
    box-shadow: var(--c-shadow-raised);
  }

  .carrier__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--c-spacing-sm);
  }

  .carrier__name {
    font-weight: 600;
  }

  .carrier__rate {
    font-family: var(--c-font-mono);
    font-size: 1.75rem;
    font-variant-numeric: tabular-nums;
    line-height: 1.1;
  }

  .carrier__meta {
    font-size: var(--c-text-sm);
    color: var(--c-text-quiet);
  }

  @media (prefers-reduced-motion: reduce) {
    .carrier {
      transition: none;
    }
  }
</style>
