<?php

declare(strict_types=1);

use App\Exceptions\Api\CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers;
use App\Exceptions\Api\CanNotRemoveOwnerFromOrganization;
use App\Exceptions\Api\ChangingRoleOfPlaceholderIsNotAllowed;
use App\Exceptions\Api\ChangingRoleToPlaceholderIsNotAllowed;
use App\Exceptions\Api\EntityStillInUseApiException;
use App\Exceptions\Api\FeatureIsNotAvailableInFreePlanApiException;
use App\Exceptions\Api\InactiveUserCanNotBeUsedApiException;
use App\Exceptions\Api\OnlyOwnerCanChangeOwnership;
use App\Exceptions\Api\OnlyPlaceholdersCanBeMergedIntoAnotherMember;
use App\Exceptions\Api\OrganizationHasNoSubscriptionButMultipleMembersException;
use App\Exceptions\Api\OrganizationNeedsAtLeastOneOwner;
use App\Exceptions\Api\PdfRendererIsNotConfiguredException;
use App\Exceptions\Api\PersonalAccessClientIsNotConfiguredException;
use App\Exceptions\Api\ThisPlaceholderCanNotBeInvitedUseTheMergeToolInsteadException;
use App\Exceptions\Api\TimeEntryCanNotBeRestartedApiException;
use App\Exceptions\Api\TimeEntryStillRunningApiException;
use App\Exceptions\Api\UserIsAlreadyMemberOfOrganizationApiException;
use App\Exceptions\Api\UserIsAlreadyMemberOfProjectApiException;
use App\Exceptions\Api\UserNotPlaceholderApiException;
use App\Service\Export\ExportException;

return [
    'api' => [
        TimeEntryStillRunningApiException::KEY => 'Zaman kaydı hâlâ devam ediyor.',
        UserNotPlaceholderApiException::KEY => 'Verilen kullanıcı bir yer tutucu değil.',
        TimeEntryCanNotBeRestartedApiException::KEY => 'Zaman kaydı zaten durdurulmuş ve yeniden başlatılamaz.',
        InactiveUserCanNotBeUsedApiException::KEY => 'Pasif kullanıcı kullanılamaz.',
        UserIsAlreadyMemberOfOrganizationApiException::KEY => 'Kullanıcı zaten organizasyonun bir üyesi.',
        UserIsAlreadyMemberOfProjectApiException::KEY => 'Kullanıcı zaten projenin bir üyesi.',
        EntityStillInUseApiException::KEY => ':modelToDelete hâlâ :modelInUse tarafından kullanılıyor ve silinemez.',
        CanNotRemoveOwnerFromOrganization::KEY => 'Organizasyonun sahibi organizasyondan çıkarılamaz.',
        CanNotDeleteUserWhoIsOwnerOfOrganizationWithMultipleMembers::KEY => 'Birden fazla üyesi olan organizasyonun sahibi olan kullanıcı silinemez. Lütfen önce organizasyonu silin.',
        OnlyOwnerCanChangeOwnership::KEY => 'Sahipliği yalnızca sahibi değiştirebilir.',
        OrganizationNeedsAtLeastOneOwner::KEY => 'Organizasyonun en az bir sahibine ihtiyacı var.',
        ChangingRoleToPlaceholderIsNotAllowed::KEY => 'Rolü yer tutucuya değiştirmek yasaktır.',
        ExportException::KEY => 'Dışa aktarma başarısız oldu, lütfen daha sonra tekrar deneyin veya destek ekibiyle iletişime geçin.',
        OrganizationHasNoSubscriptionButMultipleMembersException::KEY => 'Organizasyonun aboneliği yok ancak birden fazla üyesi var.',
        PdfRendererIsNotConfiguredException::KEY => 'PDF oluşturucu yapılandırılmamış.',
        FeatureIsNotAvailableInFreePlanApiException::KEY => 'Bu özellik ücretsiz planda mevcut değil.',
        PersonalAccessClientIsNotConfiguredException::KEY => 'Kişisel erişim istemcisi yapılandırılmamış.',
        ChangingRoleOfPlaceholderIsNotAllowed::KEY => 'Yer tutucunun rolünü değiştirmek yasaktır.',
        OnlyPlaceholdersCanBeMergedIntoAnotherMember::KEY => 'Yalnızca yer tutucular başka bir üyeyle birleştirilebilir.',
        ThisPlaceholderCanNotBeInvitedUseTheMergeToolInsteadException::KEY => 'Bu yer tutucu davet edilemez, bunun yerine birleştirme aracını kullanın.',
    ],
    'unknown_error_in_admin_panel' => 'Bilinmeyen bir hata oluştu. Lütfen logları kontrol edin.',
];