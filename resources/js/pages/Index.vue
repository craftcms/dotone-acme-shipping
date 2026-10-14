<script setup lang="ts">
  /**
   * A plugin page that owns its whole content area, and fills it with both
   * kinds of thing: a rate board built entirely from this plugin's own Vue
   * components, and below it the shipments table built from the control
   * panel's.
   *
   * The control panel supplies the chrome either way — nav, breadcrumbs,
   * title, the header action slot.
   */
  import {computed, h, ref, resolveComponent} from 'vue';
  import {
    ActionMenu,
    AdminTable,
    SearchForm,
    createCraftColumnHelper,
    useAppLayout,
    useCraftTable,
  } from '@craftcms/cp';
  import CarrierCard from '../components/CarrierCard.vue';
  import ShipmentTimeline from '../components/ShipmentTimeline.vue';
  import WeightStepper from '../components/WeightStepper.vue';
  import {useRateQuotes, type Carrier} from '../composables/useRateQuotes';
  import {t} from '../t';

  interface Shipment {
    id: number | null;
    tracking: string;
    label: string;
    url: string | null;
    shipmentUrl: string;
    status: string;
    createdAt: string | null;
  }

  const props = defineProps<{
    shipments: Shipment[];
    carriers: Carrier[];
    searchTerm?: string;
  }>();

  const LayoutSlot = resolveComponent('craft:layout-slot');
  const CpContainer = resolveComponent('craft:cp-container');

  useAppLayout({title: t('Shipments')});

  // ---------------------------------------------------------------- rate board

  const weight = ref(4);
  const selectedCarrier = ref<string | null>(null);
  const selectedShipment = ref<number | null>(props.shipments[0]?.id ?? null);

  const quotes = useRateQuotes(props.carriers, weight);

  const shipment = computed(
    () =>
      props.shipments.find((item) => item.id === selectedShipment.value) ?? null
  );

  // ------------------------------------------------------------------ the table

  const searchTerm = ref(props.searchTerm ?? '');

  const columnHelper = createCraftColumnHelper<Shipment>();

  const columns = columnHelper.columns([
    // `link` wraps the value in CpLink, so the cell navigates through Inertia
    // rather than reloading the control panel.
    columnHelper.link('label', {
      header: t('Contents'),
      props: ({row}: any) => ({href: row.original.url}),
      meta: {wrap: true},
    }),
    columnHelper.link('tracking', {
      header: t('Tracking'),
      props: ({row}: any) => ({href: row.original.shipmentUrl}),
    }),
    // A web component inside a Vue cell. The two nest without ceremony.
    columnHelper.display({
      id: 'status',
      header: t('Status'),
      cell: ({row}: any) =>
        h('craft-status', {
          status: row.original.status === 'In transit' ? 'live' : 'pending',
          label: row.original.status,
        }),
    }),
    columnHelper.date('createdAt', {header: t('Created')}),
    // `actions` right-aligns whatever vnodes it is given.
    columnHelper.actions(({row}: any) => [
      h(ActionMenu, {
        label: t('Shipment actions'),
        actions: [
          {
            type: 'link',
            href: row.original.shipmentUrl,
            label: t('Edit (Twig screen)'),
            icon: 'pencil',
          },
          {
            type: 'link',
            href: `${row.original.shipmentUrl}/rate`,
            label: t('Rate quote (Blade screen)'),
            icon: 'calculator',
          },
          {
            type: 'link',
            href: `${row.original.shipmentUrl}/legacy`,
            label: t('Craft 5 template'),
            icon: 'clock-rotate-left',
          },
        ],
      }),
    ]),
  ]);

  const table = useCraftTable({
    get columns() {
      return columns;
    },
    get data() {
      return props.shipments;
    },
  });
</script>

<template>
  <LayoutSlot name="content-actions">
    <craft-button variant="primary">{{ t('Book shipment') }}</craft-button>
  </LayoutSlot>

  <CpContainer>
    <!-- Entirely this plugin's own components. -->
    <div class="board">
      <div class="board__main">
        <h2 class="board__heading">{{ t('Rate board') }}</h2>
        <WeightStepper v-model="weight" />
        <div class="board__quotes">
          <CarrierCard
            v-for="quote in quotes"
            :key="quote.handle"
            :quote="quote"
            :selected="quote.handle === selectedCarrier"
            @select="selectedCarrier = $event"
          />
        </div>
      </div>

      <aside class="board__side">
        <h2 class="board__heading">{{ t('Tracking') }}</h2>
        <nav class="board__picker" :aria-label="t('Recent shipments')">
          <button
            v-for="item in props.shipments.slice(0, 6)"
            :key="item.id ?? item.tracking"
            type="button"
            class="board__pick"
            :class="{'board__pick--on': item.id === selectedShipment}"
            :aria-pressed="item.id === selectedShipment"
            @click="selectedShipment = item.id"
          >
            {{ item.tracking }}
          </button>
        </nav>

        <ShipmentTimeline
          v-if="shipment"
          :tracking="shipment.tracking"
          :status="shipment.status"
          :created-at="shipment.createdAt"
        />
      </aside>
    </div>

    <!-- And the same content area, filled with the control panel's. -->
    <section class="shipments">
      <h2 class="board__heading">{{ t('All shipments') }}</h2>

      <!--
        SearchForm's default partial reload asks for props named `data` and
        `searchTerm`, which is the admin-table convention. This page calls its
        prop `shipments`, so `only` has to say so.
      -->
      <SearchForm
        v-model="searchTerm"
        action="acme-shipping"
        :options="{only: ['shipments', 'searchTerm']}"
      />

      <AdminTable
        layout="auto"
        :table="table"
        :from="1"
        :to="props.shipments.length"
        :total="props.shipments.length"
        :reorderable="false"
        :empty-message="t('No shipments match.')"
      />
    </section>
  </CpContainer>
</template>

<style scoped>
  /* Tokens rather than Tailwind: the control panel's utility build does not
     scan plugin files, so a class it never uses itself would not exist. */
  .board {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
    gap: var(--c-spacing-xl);
    padding-block: var(--c-spacing-xl);
  }

  .board__main,
  .board__side {
    display: grid;
    gap: var(--c-spacing-md);
    align-content: start;
    min-width: 0;
  }

  .board__heading {
    margin: 0;
    font-size: var(--c-text-sm);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--c-text-quiet);
  }

  .board__quotes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));
    gap: var(--c-spacing-md);
  }

  .board__picker {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-xs);
  }

  .board__pick {
    font: inherit;
    font-family: var(--c-font-mono);
    font-size: var(--c-text-xs);
    padding: var(--c-spacing-xs) var(--c-spacing-sm);
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-full);
    background: var(--c-surface-raised);
    color: var(--c-text-quiet);
    cursor: pointer;
  }

  .board__pick--on {
    border-color: var(--c-color-accent-border-loud);
    color: var(--c-text-default);
  }

  .shipments {
    display: grid;
    gap: var(--c-spacing-md);
    padding-block-end: var(--c-spacing-xl);
    border-top: 1px solid var(--c-color-neutral-border-quiet);
    padding-block-start: var(--c-spacing-xl);
  }

  @media (max-width: 60rem) {
    .board {
      grid-template-columns: minmax(0, 1fr);
    }
  }
</style>
