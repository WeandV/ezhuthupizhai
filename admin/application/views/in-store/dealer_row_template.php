<tr class="main-row">
    <td><?= $dealer->id; ?></td>
    <td><?= $dealer->name; ?></td>
    <td><?= $dealer->contact_person; ?></td>
    <td><?= $dealer->email; ?></td>
    <td><?= $dealer->phone; ?></td>
    <td>
        <button class="toggle-details-btn">
            <i class="fa-solid fa-angles-down text-primary"></i>
        </button>
    </td>
    <td>
        <div class="d-inline-flex gap-1">
            <button type="button" class="btn p-1" data-bs-toggle="modal" data-bs-target="#editModal<?= $dealer->id ?>">
                <i class="fa-solid fa-pen fs-13 text-success"></i>
            </button>
            <div class="modal fade" id="editModal<?= $dealer->id ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $dealer->id ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel<?= $dealer->id ?>">Edit Dealer: <?= htmlspecialchars($dealer->name ?? '') ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="<?= base_url('dashboard/update_dealer_modal') ?>" method="post">
                                <input type="hidden" name="id" value="<?= $dealer->id ?? '' ?>">
                                <div class="mb-3">
                                    <label for="name_<?= $dealer->id ?>" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name_<?= $dealer->id ?>" name="name" value="<?= htmlspecialchars($dealer->name ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="contact_person_<?= $dealer->id ?>" class="form-label">Contact Person</label>
                                    <input type="text" class="form-control" id="contact_person_<?= $dealer->id ?>" name="contact_person" value="<?= htmlspecialchars($dealer->contact_person ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="email_<?= $dealer->id ?>" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email_<?= $dealer->id ?>" name="email" value="<?= htmlspecialchars($dealer->email ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="phone_<?= $dealer->id ?>" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone_<?= $dealer->id ?>" name="phone" value="<?= htmlspecialchars($dealer->phone ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="address1_<?= $dealer->id ?>" class="form-label">Address Line 1</label>
                                    <input type="text" class="form-control" id="address1_<?= $dealer->id ?>" name="address_line1" value="<?= htmlspecialchars($dealer->address_line1 ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="address2_<?= $dealer->id ?>" class="form-label">Address Line 2</label>
                                    <input type="text" class="form-control" id="address2_<?= $dealer->id ?>" name="address_line2" value="<?= htmlspecialchars($dealer->address_line2 ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="city_<?= $dealer->id ?>" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city_<?= $dealer->id ?>" name="city" value="<?= htmlspecialchars($dealer->city ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="state_<?= $dealer->id ?>" class="form-label">State</label>
                                    <input type="text" class="form-control" id="state_<?= $dealer->id ?>" name="state" value="<?= htmlspecialchars($dealer->state ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="pincode_<?= $dealer->id ?>" class="form-label">Pincode</label>
                                    <input type="text" class="form-control" id="pincode_<?= $dealer->id ?>" name="pincode" value="<?= htmlspecialchars($dealer->pincode ?? '') ?>">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <a href="<?= base_url('dashboard/delete_dealer/' . $dealer->id); ?>"
                class="btn p-1"
                onclick="return confirm('Are you sure you want to delete this dealer? This action cannot be undone.');">
                <i class="fa-solid fa-trash fs-13 text-danger"></i>
            </a>
        </div>
    </td>
</tr>
<tr class="details-row" style="display: none;">
    <td colspan="7">
        <p class="mb-1"><strong>Address:</strong></p>
        <?php if (!empty($dealer->address_line1)) : ?>
            <p class="mb-1"><?= $dealer->address_line1; ?>,</p>
        <?php endif; ?>
        <?php if (!empty($dealer->address_line2)) : ?>
            <p class="mb-1"><?= $dealer->address_line2; ?>,</p>
        <?php endif; ?>
        <?php if (!empty($dealer->city)) : ?>
            <p class="mb-1"><?= $dealer->city; ?>,</p>
        <?php endif; ?>
        <?php if (!empty($dealer->state)) : ?>
            <p class="mb-1"><?= $dealer->state; ?>,</p>
        <?php endif; ?>
        <?php if (!empty($dealer->pincode)) : ?>
            <p class="mb-1"><?= $dealer->pincode; ?></p>
        <?php endif; ?>
    </td>
</tr>
