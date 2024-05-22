
import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting('checkview_data', {});

const defaultLabel = __(
	'CheckView Testing',
	'woo-gutenberg-products-block'
);

const label = decodeEntities(settings.title) || defaultLabel;
/**
 * Content component
 */
const Content = () => {
	return decodeEntities(settings.description || '');
};
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = (props) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={label} />;
};

/**
 * Checkview payment method config object.
 */
const Checkview = {
	name: "checkview",
	label: <Label />,
	content: <Content />,
	edit: <Content />,
	paymentMethodId: 'checkview',
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: settings.supports,
	},
};

registerPaymentMethod(Checkview);