import {
    Button,
    Flex,
    FlexItem,
    __experimentalTruncate as Truncate,
    __experimentalZStack as ZStack,
    Icon
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { generateResponsiveIndicators } from './utilities';
import { trash, dragHandle } from '@wordpress/icons';

/**
 * Sortable Item Component
 *
 * @param {Object}   props
 * @param {Object}   props.item     - The item to be displayed.
 * @param {boolean}  props.isActive - Whether the item is active.
 * @param {Function} props.onClick  - Function to call when the item is clicked.
 * @param {Function} props.onDelete - Function to call when the delete button is clicked.
 */
export const SortableItem = ({ item, isActive, onClick, onDelete }) => {
	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
	} = useSortable({ id: item.id });

	const style = {
		transform: CSS.Transform.toString(transform),
		transition,
		backgroundColor: isActive ? '#f0f0f0' : '#fff',
		boxShadow: isActive
			? 'inset 0 0 0 1px var(--wp-components-color-accent, var(--wp-admin-theme-color, #3858e9)), 0 0 0 currentColor'
			: 'none',
		marginBottom: '1rem',
		cursor: 'grab',
	};

    const indicators = generateResponsiveIndicators(item, item?.id);

	return (
		<Flex ref={setNodeRef} style={style}>
            <FlexItem style={{ width: '10%' }}>
                <div
                    {...attributes}
                    {...listeners}
                    style={{
                        cursor: 'grab',
                        margin: '0 0 0 8px',
                        display: 'flex',
                        alignItems: 'center',
                    }}
                >
                    <Icon icon={dragHandle} />
                </div>
            </FlexItem>
            <FlexItem style={{ width: '90%' }}>
                <Flex>
                    <Button
                        __next40pxDefaultSize
                        variant="secondary"
                        isPressed={isActive}
                        onClick={onClick}
                        style={{
                            '--wp-admin-theme-color': '#d8d8d8',
                            color: '#1e1e1e',
                            '--wp-components-color-foreground': isActive ? '#f0f0f0' : '#1e1e1e',
                            width: '100%',
                            textAlign: 'left',
                            boxShadow: 'none',
                            margin: '1px 0',
                            paddingLeft: 0,
                        }}
                    >
                        <Flex align="center" justify="start" gap={2}>
                            <ZStack offset={20} isLayered style={ { width: 'auto' } }>
                                {indicators}
                            </ZStack>
                            <Truncate limit={50} style={{ maxWidth: '75%' }}>
                                {item?.desktop?.media?.filename || __('Media Item')}
                            </Truncate>
                        </Flex>
                    </Button>
                    <Button
                        __next40pxDefaultSize
                        variant="tertiary"
                        size="compact"
                        icon={trash}
                        isDestructive
                        onClick={onDelete}
                    />
                </Flex>
            </FlexItem>
		</Flex>
	);
};
