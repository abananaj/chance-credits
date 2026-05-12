import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { SelectControl, Spinner } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

const TEAM_EXCLUDE_GROUPS = ['actor', 'producer'];

const VARIATION_OPTIONS = [
  { label: __('All Credits', 'theatrum-blocks'), value: 'all' },
  { label: __('Production Team', 'theatrum-blocks'), value: 'team' },
  { label: __('Production Cast', 'theatrum-blocks'), value: 'cast' },
  { label: __('Production Partners', 'theatrum-blocks'), value: 'partner' },
];

const VARIATION_LABELS = {
  all: 'Production Credits',
  team: 'Production Team',
  cast: 'Production Cast',
  partner: 'Production Partners',
};

function filterByRoleGroup(credits, roleGroup) {
  if (roleGroup === 'all' || !roleGroup) return credits;
  if (roleGroup === 'team') return credits.filter((c) => !TEAM_EXCLUDE_GROUPS.includes(c.role_group));
  if (roleGroup === 'cast') return credits.filter((c) => c.role_group === 'actor');
  if (roleGroup === 'partner') return credits.filter((c) => c.role_group === 'producer');
  return credits;
}

export default function Edit({ attributes, setAttributes }) {
  const { roleGroup = 'all' } = attributes;
  const blockProps = useBlockProps();
  const [credits, setCredits] = useState([]);
  const [isLoading, setIsLoading] = useState(false);

  const postId = useSelect((select) => select('core/editor').getCurrentPostId());

  useEffect(() => {
    if (!postId) {
      setCredits([]);
      return;
    }

    setIsLoading(true);

    apiFetch({ path: `/chance/v1/production-credits/${postId}` })
      .then((data) => {
        setCredits(filterByRoleGroup(data.credits || [], roleGroup));
        setIsLoading(false);
      })
      .catch((error) => {
        console.error('Error fetching production credits:', error);
        setCredits([]);
        setIsLoading(false);
      });
  }, [postId, roleGroup]);

  const decodeHtmlEntities = (text) => {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = text;
    return textarea.value;
  };

  const listItems = credits.map((credit) => {
    const artistTitle = decodeHtmlEntities(credit.artist_title);
    const role = credit.role ? decodeHtmlEntities(credit.role) : '';

    return (
      <li key={credit.id} className="credit">
        {credit.artist_thumbnail && (
          <img src={credit.artist_thumbnail} alt={artistTitle} className="artist-headshot" />
        )}
        <p className="artist"><a href={credit.artist_url}>{artistTitle}</a></p>
        {role && (
          <p className="role">{role}</p>
        )}
      </li>
    );
  });

  return (
    <>
      <InspectorControls>
        <div style={{ padding: '16px' }}>
          <SelectControl
            label={__('Display', 'theatrum-blocks')}
            value={roleGroup}
            options={VARIATION_OPTIONS}
            onChange={(value) => setAttributes({ roleGroup: value })}
          />
        </div>
      </InspectorControls>
      <div {...blockProps}>
        <p className="production-credits-label">{VARIATION_LABELS[roleGroup] || VARIATION_LABELS.all}</p>
        {isLoading ? (
          <Spinner />
        ) : (
          <ul className="production-credits-ul">
            {listItems.length > 0 ? listItems : <li>No credits found</li>}
          </ul>
        )}
      </div>
    </>
  );
}

