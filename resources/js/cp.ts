import type {InertiaPageComponent} from '@craftcms/cp';
import ShipmentsPanel from './components/ShipmentsPanel.vue';
import ShipmentsPanelActions from './components/ShipmentsPanelActions.vue';
import RateQuote from './components/RateQuote.vue';
import Index from './pages/Index.vue';

window.Cp.booting((cp) => {
  cp.$inertia.register(
    'acme-shipping/Index',
    Index as unknown as InertiaPageComponent
  );

  cp.$components.register('acme-shipping:rate-quote', RateQuote);

  cp.$elementDetailsPanels.register({
    id: 'acme-shipping:shipments',
    label: 'Shipments',
    icon: 'truck',
    component: ShipmentsPanel,
    headerActionsComponent: ShipmentsPanelActions,
    order: 30,
    props: ({payload, active, refreshToken}: any) => ({
      payload,
      active,
      refreshToken,
    }),
  });
});
