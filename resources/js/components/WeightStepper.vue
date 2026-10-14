<script setup lang="ts">
  /** A control the control panel does not ship, built from its primitives. */
  import {t} from '../t';

  const weight = defineModel<number>({required: true});

  const step = (by: number) => {
    weight.value = Math.min(30, Math.max(1, weight.value + by));
  };
</script>

<template>
  <div class="stepper">
    <span class="stepper__label">{{ t('Billable weight') }}</span>
    <div class="stepper__controls">
      <craft-button
        :aria-label="t('Decrease weight')"
        icon="minus"
        @click="step(-1)"
      ></craft-button>
      <output class="stepper__value" aria-live="polite">
        {{ weight }} <span class="stepper__unit">kg</span>
      </output>
      <craft-button
        :aria-label="t('Increase weight')"
        icon="plus"
        @click="step(1)"
      ></craft-button>
    </div>
  </div>
</template>

<style scoped>
  /* Tokens rather than Tailwind: the control panel's utility build does not
     scan plugin files, so a class it never uses itself would not exist. */
  .stepper {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: var(--c-spacing-md);
    padding: var(--c-spacing-md) var(--c-spacing-lg);
    background: var(--c-surface-raised);
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-lg);
  }

  .stepper__label {
    font-size: var(--c-text-sm);
    color: var(--c-text-quiet);
  }

  .stepper__controls {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  .stepper__value {
    min-width: 4.5rem;
    text-align: center;
    font-family: var(--c-font-mono);
    font-size: var(--c-text-lg);
    font-variant-numeric: tabular-nums;
  }

  .stepper__unit {
    color: var(--c-text-quiet);
    font-size: var(--c-text-sm);
  }
</style>
