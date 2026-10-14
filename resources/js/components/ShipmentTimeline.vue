<script setup lang="ts">
  import {computed} from 'vue';
  import {t} from '../t';

  const props = defineProps<{
    tracking: string;
    status: string;
    createdAt: string | null;
  }>();

  const stages = computed(() => {
    const created = props.createdAt ? new Date(props.createdAt) : new Date();
    const inTransit = props.status === 'In transit';

    const at = (days: number) => {
      const date = new Date(created);
      date.setDate(date.getDate() + days);

      return date;
    };

    return [
      {label: t('Label created'), date: created, done: true},
      {label: t('Collected'), date: at(1), done: inTransit},
      {label: t('In transit'), date: at(2), done: inTransit},
      {label: t('Delivered'), date: at(4), done: false},
    ];
  });

  const when = (date: Date) =>
    new Intl.DateTimeFormat(undefined, {
      month: 'short',
      day: 'numeric',
    }).format(date);
</script>

<template>
  <section class="timeline" :aria-label="t('Tracking history')">
    <h2 class="timeline__title">{{ props.tracking }}</h2>
    <ol class="timeline__list">
      <li
        v-for="stage in stages"
        :key="stage.label"
        class="timeline__stage"
        :class="{'timeline__stage--done': stage.done}"
      >
        <span class="timeline__dot" aria-hidden="true"></span>
        <span class="timeline__label">{{ stage.label }}</span>
        <time class="timeline__when">{{ when(stage.date) }}</time>
      </li>
    </ol>
  </section>
</template>

<style scoped>
  .timeline {
    padding: var(--c-spacing-lg);
    background: var(--c-surface-sunken);
    border-radius: var(--c-radius-lg);
  }

  .timeline__title {
    margin: 0 0 var(--c-spacing-md);
    font-family: var(--c-font-mono);
    font-size: var(--c-text-sm);
    color: var(--c-text-quiet);
  }

  .timeline__list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: var(--c-spacing-md);
  }

  .timeline__stage {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: var(--c-spacing-sm);
    font-size: var(--c-text-sm);
    color: var(--c-text-quiet);
    position: relative;
  }

  /* The connecting rule, drawn from each dot to the next. */
  .timeline__stage:not(:last-child)::before {
    content: '';
    position: absolute;
    inset-inline-start: calc(var(--c-spacing-xs) + 1px);
    top: 1rem;
    bottom: calc(-1 * var(--c-spacing-md));
    width: 1px;
    background: var(--c-color-neutral-border-quiet);
  }

  .timeline__stage--done {
    color: var(--c-text-default);
  }

  .timeline__dot {
    inline-size: 0.6rem;
    block-size: 0.6rem;
    border-radius: var(--c-radius-full);
    border: 1px solid var(--c-color-neutral-border-quiet);
    background: var(--c-surface-default);
  }

  .timeline__stage--done .timeline__dot {
    background: var(--c-color-static-success-fill);
    border-color: var(--c-color-static-success-fill);
  }

  .timeline__when {
    font-variant-numeric: tabular-nums;
  }
</style>
