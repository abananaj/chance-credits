/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * WordPress dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit() {
	const blockProps = useBlockProps();
	const [credits, setCredits] = useState([]);
	const [isLoading, setIsLoading] = useState(false);

	// Get current post ID
	const postId = useSelect((select) => select('core/editor').getCurrentPostId());

	// Fetch credits when post ID changes
	useEffect(() => {
		if (!postId) {
			setCredits([]);
			return;
		}

		setIsLoading(true);

		// Fetch credits using REST endpoint
		apiFetch({ path: `/chance/v1/artist-credits/${postId}` })
			.then((data) => {
				setCredits(data.credits || []);
				setIsLoading(false);
			})
			.catch((error) => {
				console.error('Error fetching artist credits:', error);
				setCredits([]);
				setIsLoading(false);
			});
	}, [postId]);

	// Helper function to decode HTML entities
	const decodeHtmlEntities = (text) => {
		const textarea = document.createElement('textarea');
		textarea.innerHTML = text;
		return textarea.value;
	};

	const listItems = credits.map((credit) => {
		const productionTitle = decodeHtmlEntities(credit.production_title);
		const role = credit.role ? decodeHtmlEntities(credit.role) : '';
		const date = credit.date ? decodeHtmlEntities(credit.date) : '';

		return (
			<li key={credit.id}>
				<a href={credit.production_url}>
					<span className="production">{productionTitle}</span>
				</a>
				{role && (
					<>
						, <span className="role">{role}</span>
					</>
				)}
				{date && <span className="date">{date}</span>}
			</li>
		);
	});

	return (
		<div {...blockProps}>
			{isLoading ? (
				<Spinner />
			) : (
				<ul className="artist-credits-ul">
					{listItems.length > 0 ? listItems : <li>No credits found</li>}
				</ul>
			)}
		</div>
	);
}
