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

export { cn } from './utils/cn';
export { buttonVariants, type ButtonVariants } from './Buttons/index';

import PrimaryButton from './Buttons/PrimaryButton.vue';
import SecondaryButton from './Buttons/SecondaryButton.vue';
import Button from './Buttons/Button.vue';
import TimeTrackerStartStop from './TimeTrackerStartStop.vue';
import ProjectBadge from './Project/ProjectBadge.vue';
import LoadingSpinner from './LoadingSpinner.vue';
import Modal from './Modal.vue';
import TextInput from './Input/TextInput.vue';
import InputLabel from './Input/InputLabel.vue';
import TimeTrackerRunningInDifferentOrganizationOverlay from './TimeTracker/TimeTrackerRunningInDifferentOrganizationOverlay.vue';
import TimeTrackerControls from './TimeTracker/TimeTrackerControls.vue';
import TimeTrackerMoreOptionsDropdown from './TimeTracker/TimeTrackerMoreOptionsDropdown.vue';
import CardTitle from './CardTitle.vue';
import SelectDropdown from './Input/SelectDropdown.vue';
import Badge from './Badge.vue';
import Checkbox from './Input/Checkbox.vue';
import TimeEntryGroupedTable from './TimeEntry/TimeEntryGroupedTable.vue';
import TimeEntryMassActionRow from './TimeEntry/TimeEntryMassActionRow.vue';
import TimeEntryCreateModal from './TimeEntry/TimeEntryCreateModal.vue';
import TimeEntryEditModal from './TimeEntry/TimeEntryEditModal.vue';
import MoreOptionsDropdown from './MoreOptionsDropdown.vue';
import FullCalendarEventContent from './FullCalendar/FullCalendarEventContent.vue';
import FullCalendarDayHeader from './FullCalendar/FullCalendarDayHeader.vue';
import TimeEntryCalendar from './FullCalendar/TimeEntryCalendar.vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from './tooltip/index';
export type { ActivityPeriod } from './FullCalendar/idleStatusPlugin';

export {
    money,
    color,
    random,
    time,
    Button,
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
    TimeTrackerMoreOptionsDropdown,
    CardTitle,
    SelectDropdown,
    Badge,
    Checkbox,
    TimeEntryGroupedTable,
    TimeEntryMassActionRow,
    MoreOptionsDropdown,
    TimeEntryCreateModal,
    TimeEntryEditModal,
    FullCalendarEventContent,
    FullCalendarDayHeader,
    TimeEntryCalendar,
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
};
