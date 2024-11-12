declare global {
    interface Window {
        getWeekStartSetting: () => string;
        getTimezoneSetting: () => string;
    }
}

import * as money from './utils/money';
import * as color from './utils/color';
import * as random from './utils/random';
import * as time from './utils/time';

import PrimaryButton from './Buttons/PrimaryButton.vue';
import SecondaryButton from './Buttons/SecondaryButton.vue';
import TimeTrackerStartStop from './TimeTrackerStartStop.vue';
import ProjectBadge from './Project/ProjectBadge.vue';
import LoadingSpinner from './LoadingSpinner.vue';
import Modal from './Modal.vue';
import TextInput from './Input/TextInput.vue';
import InputLabel from './Input/InputLabel.vue';
import TimeTrackerRunningInDifferentOrganizationOverlay from './TimeTracker/TimeTrackerRunningInDifferentOrganizationOverlay.vue';
import TimeTrackerControls from './TimeTracker/TimeTrackerControls.vue';
import CardTitle from './CardTitle.vue';
import SelectDropdown from './Input/SelectDropdown.vue';
import Badge from './Badge.vue';
import Checkbox from './Input/Checkbox.vue';
import TimeEntryGroupedTable from './TimeEntry/TimeEntryGroupedTable.vue';
import TimeEntryMassActionRow from './TimeEntry/TimeEntryMassActionRow.vue';
import TimeEntryCreateModal from './TimeEntry/TimeEntryCreateModal.vue';
import MoreOptionsDropdown from './MoreOptionsDropdown.vue';

export {
    money,
    color,
    random,
    time,
    PrimaryButton,
    SecondaryButton,
    TimeTrackerStartStop,
    ProjectBadge,
    LoadingSpinner,
    Modal,
    TextInput,
    InputLabel,
    TimeTrackerRunningInDifferentOrganizationOverlay,
    TimeTrackerControls,
    CardTitle,
    SelectDropdown,
    Badge,
    Checkbox,
    TimeEntryGroupedTable,
    TimeEntryMassActionRow,
    MoreOptionsDropdown,
    TimeEntryCreateModal,
};
