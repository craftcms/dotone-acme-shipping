<script setup lang="ts">
  import {h, resolveComponent} from 'vue';
  import {
    AdminTable,
    createCraftColumnHelper,
    useAppLayout,
    useCraftTable,
  } from '@craftcms/cp';

  interface Shipment {
    id: number | null;
    tracking: string;
    label: string;
    url: string | null;
    shipmentUrl: string;
    status: string;
    createdAt: string | null;
  }

  const props = defineProps<{shipments: Shipment[]}>();

  const LayoutSlot = resolveComponent('craft:layout-slot');
  const CpContainer = resolveComponent('craft:cp-container');
  const RateQuote = resolveComponent('acme-shipping:rate-quote');

  useAppLayout({
    title: 'Shipments',
  });

  const columnHelper = createCraftColumnHelper<Shipment>();

  const columns = columnHelper.columns([
    // `link` wraps the value in CpLink, so the cell navigates through Inertia
    // rather than reloading the control panel.
    columnHelper.link('label', {
      header: 'Contents',
      props: ({row}: any) => ({href: row.original.url}),
      meta: {wrap: true},
    }),
    columnHelper.link('tracking', {
      header: 'Tracking',
      props: ({row}: any) => ({href: row.original.shipmentUrl}),
    }),
    columnHelper.accessor('status', {
      header: 'Status',
    }),
    columnHelper.date('createdAt', {header: 'Created'}),
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
    <craft-button variant="primary">Sync now</craft-button>
  </LayoutSlot>
  <CpContainer>
    <AdminTable
      layout="auto"
      :table="table"
      :from="1"
      :to="props.shipments.length"
      :total="props.shipments.length"
      :reorderable="false"
      empty-message="No shipments yet."
    />
  </CpContainer>
</template>
