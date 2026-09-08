import { describe, it, expect, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import Pagination from "./Pagination";

describe("Pagination", () => {
  it("renders nothing when there is only one page", () => {
    const { container } = render(<Pagination currentPage={1} lastPage={1} onPageChange={vi.fn()} />);

    expect(container).toBeEmptyDOMElement();
  });

  it("disables Prev on the first page and Next on the last page", () => {
    render(<Pagination currentPage={1} lastPage={3} onPageChange={vi.fn()} />);

    expect(screen.getByRole("button", { name: /previous page/i })).toBeDisabled();
    expect(screen.getByRole("button", { name: /next page/i })).not.toBeDisabled();
  });

  it("calls onPageChange with the target page when a page number is clicked", async () => {
    const onPageChange = vi.fn();
    render(<Pagination currentPage={1} lastPage={3} onPageChange={onPageChange} />);

    await userEvent.click(screen.getByRole("button", { name: "2" }));

    expect(onPageChange).toHaveBeenCalledWith(2);
  });

  it("calls onPageChange with currentPage + 1 when Next is clicked", async () => {
    const onPageChange = vi.fn();
    render(<Pagination currentPage={2} lastPage={5} onPageChange={onPageChange} />);

    await userEvent.click(screen.getByRole("button", { name: /next page/i }));

    expect(onPageChange).toHaveBeenCalledWith(3);
  });

  it("marks the current page with aria-current", () => {
    render(<Pagination currentPage={2} lastPage={3} onPageChange={vi.fn()} />);

    expect(screen.getByRole("button", { name: "2" })).toHaveAttribute("aria-current", "page");
  });
});
